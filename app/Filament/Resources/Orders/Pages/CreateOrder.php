<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Discount;
use App\Models\MenuItem;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Str;

class CreateOrder extends CreateRecord
{
    use HasWizard;

    protected static string $resource = OrderResource::class;

    public function getSteps(): array
    {
        return [
            Step::make('Customer & Order Details')
                ->schema([
                    Select::make('user_id')
                        ->relationship(
                            name: 'user',
                            titleAttribute: 'name',
                            modifyQueryUsing: function ($query) {
                                $restaurantId = Filament::getTenant()?->id;

                                return $query->whereIn('id', function ($subquery) use ($restaurantId) {
                                    $subquery->select('user_id')
                                        ->from('orders')
                                        ->where('restaurant_id', $restaurantId)
                                        ->whereNotNull('user_id');
                                });
                            }
                        )
                        ->label('Customer')
                        ->placeholder('Guest')
                        ->nullable()
                        ->searchable()
                        ->preload(),
                    Select::make('type')
                        ->options([
                            'delivery' => 'Delivery',
                            'pickup' => 'Pickup',
                        ])->native(false)
                        ->required()
                        ->default('pickup')
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            static::calculateTotals($get, $set);
                        }),
                    DateTimePicker::make('estimated_ready_time')
                        ->label('Estimated Ready Time')
                        ->default(fn ($get) => $get('type') === 'delivery'
                            ? now()->addMinutes(Filament::getTenant()?->estimated_delivery_time ?? 30)
                            : now()->addMinutes(Filament::getTenant()?->estimated_pickup_time ?? 20)
                        ),
                    Textarea::make('special_instructions')
                        ->rows(2)
                        ->columnSpanFull(),
                    Textarea::make('delivery_address')
                        ->rows(2)
                        ->columnSpanFull()
                        ->visible(fn ($get) => $get('type') === 'delivery')
                        ->required(fn ($get) => $get('type') === 'delivery'),
                ])->columns(2),

            Step::make('Order Items')
                ->schema([
                    Repeater::make('orderItems')
                        ->relationship('orderItems')
                        ->schema([
                            Select::make('menu_item_id')
                                ->relationship(
                                    name: 'menuItem',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn ($query) => $query->where('restaurant_id', Filament::getTenant()?->id)->where('is_available', true)
                                )
                                ->label('Item')->searchable()->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $item = MenuItem::find($state);
                                    $set('price', $item ? $item->price / 100 : 0);
                                    static::calculateTotals($get, $set);
                                }),
                            TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->minValue(1)
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    static::calculateTotals($get, $set);
                                }),
                            TextInput::make('price')
                                ->numeric()
                                ->prefix('GHS')
                                ->readonly()
                                ->dehydrated()
                                ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                                ->formatStateUsing(fn ($state) => $state / 100)
                                ->required(),
                            TextEntry::make('line_total')
                                ->label('Line Total')
                                ->getStateUsing(fn ($get) => 'GHS '.number_format(((float) $get('price') ?: 0.0) * ((int) $get('quantity') ?: 1), 2)),
                            TextInput::make('notes')
                                ->placeholder('No onions, extra spicy, etc.'),
                        ])
                        ->columns(5)
                        ->defaultItems(1)
                        ->live()
                        ->afterStateUpdated(function ($set, $get) {
                            static::calculateTotals($get, $set);
                        }),
                    Section::make([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('summary_subtotal')
                                    ->label('Subtotal')
                                    ->getStateUsing(fn ($get) => 'GHS '.number_format((float) ($get('subtotal') ?: 0.0), 2)),
                                TextEntry::make('summary_tax')
                                    ->label('Tax (15%)')
                                    ->getStateUsing(fn ($get) => 'GHS '.number_format((float) ($get('tax') ?: 0.0), 2)),
                                TextEntry::make('summary_total')
                                    ->label('Estimated Total')
                                    ->getStateUsing(fn ($get) => 'GHS '.number_format((float) ($get('total') ?: 0.0), 2)),
                            ])
                            ->extraAttributes(['class' => 'border-t pt-4 mt-4']),
                    ]),
                ]),

            Step::make('Payment & Review')
                ->schema([
                    Select::make('payment_method')
                        ->options([
                            'mock' => 'Mock Payment',
                            'card' => 'Credit/Debit Card',
                        ])
                        ->required()
                        ->default('mock'),
                    Select::make('payment_status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'failed' => 'Failed',
                        ])
                        ->required()
                        ->default('pending'),
                    Select::make('promo_code')
                        ->options(fn () => Discount::where('restaurant_id', Filament::getTenant()?->id)->where('is_active', true)->pluck('code', 'code'))
                        ->placeholder('Select promo code')
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $discountRecord = Discount::where('restaurant_id', Filament::getTenant()?->id)
                                ->where('code', $state)
                                ->where('is_active', true)
                                ->first();
                            if ($discountRecord) {
                                $subtotal = (float) ($get('subtotal') ?: 0.0);
                                $discountVal = 0.0;
                                if ($subtotal >= ($discountRecord->min_order_amount / 100)) {
                                    if ($discountRecord->type === 'fixed') {
                                        $discountVal = $discountRecord->value / 100;
                                    } else {
                                        $discountVal = $subtotal * ($discountRecord->value / 100);
                                    }
                                }
                                $set('discount', $discountVal);
                            } else {
                                $set('discount', 0);
                            }
                            static::calculateTotals($get, $set);
                        }),

                    TextInput::make('subtotal')
                        ->numeric()
                        ->prefix('GHS')
                        ->readonly()
                        ->dehydrated()
                        ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                        ->formatStateUsing(fn ($state) => $state / 100)
                        ->placeholder('Calculated automatically'),
                    TextInput::make('delivery_fee')
                        ->numeric()
                        ->prefix('GHS')
                        ->readonly()
                        ->dehydrated()
                        ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                        ->formatStateUsing(fn ($state) => $state / 100)
                        ->placeholder('Calculated automatically'),
                    TextInput::make('service_fee')
                        ->numeric()
                        ->prefix('GHS')
                        ->readonly()
                        ->dehydrated()
                        ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                        ->formatStateUsing(fn ($state) => $state / 100)
                        ->placeholder('Calculated automatically'),
                    TextInput::make('tax')
                        ->numeric()
                        ->prefix('GHS')
                        ->readonly()
                        ->dehydrated()
                        ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                        ->formatStateUsing(fn ($state) => $state / 100)
                        ->placeholder('Calculated automatically'),
                    TextInput::make('discount')
                        ->numeric()
                        ->prefix('GHS')
                        ->readonly()
                        ->dehydrated()
                        ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                        ->formatStateUsing(fn ($state) => $state / 100)
                        ->default(0),
                    TextInput::make('total')
                        ->numeric()
                        ->prefix('GHS')
                        ->readonly()
                        ->dehydrated()
                        ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                        ->formatStateUsing(fn ($state) => $state / 100)
                        ->placeholder('Calculated automatically'),
                ])->columns(3),
        ];
    }

    public static function calculateTotals(callable $get, callable $set): void
    {
        $items = $get('orderItems') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            if (! empty($item['menu_item_id']) && ! empty($item['quantity'])) {
                $menuItem = MenuItem::find($item['menu_item_id']);
                if ($menuItem) {
                    $subtotal += $menuItem->price * (int) $item['quantity'];
                }
            }
        }

        $set('subtotal', $subtotal / 100);

        $restaurant = Filament::getTenant();
        $deliveryFee = 0;
        if ($get('type') === 'delivery' && $restaurant) {
            $deliveryFee = $restaurant->delivery_fee;
        }
        $set('delivery_fee', $deliveryFee / 100);

        // Service fee: default GHS 2.00 (200 cents)
        $serviceFee = 200;
        $set('service_fee', $serviceFee / 100);

        // Tax: sum active rates of tenant restaurant
        $tax = 0;
        if ($restaurant) {
            $activeTaxesQuery = $restaurant->taxes()->where('is_active', true);
            if ($activeTaxesQuery->exists()) {
                $taxRate = $activeTaxesQuery->sum('rate');
                $tax = (int) round($subtotal * ($taxRate / 100));
            } else {
                $tax = (int) ($subtotal * 0.15); // fallback to 15%
            }
        } else {
            $tax = (int) ($subtotal * 0.15);
        }
        $set('tax', $tax / 100);

        // Discount
        $discount = (int) (($get('discount') ?? 0) * 100);

        // Total calculation
        $total = max(0, $subtotal + $deliveryFee + $serviceFee + $tax - $discount);
        $set('total', $total / 100);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $restaurant = Filament::getTenant();

        $subtotal = 0;
        $orderItems = $data['orderItems'] ?? $this->data['orderItems'] ?? [];
        if (! empty($orderItems)) {
            foreach ($orderItems as $item) {
                if (! empty($item['menu_item_id']) && ! empty($item['quantity'])) {
                    $menuItem = MenuItem::find($item['menu_item_id']);
                    if ($menuItem) {
                        $subtotal += $menuItem->price * (int) $item['quantity'];
                    }
                }
            }
        }

        $deliveryFee = 0;
        if (($data['type'] ?? '') === 'delivery' && $restaurant) {
            $deliveryFee = $restaurant->delivery_fee;
        }

        $serviceFee = 200;
        $tax = 0;
        if ($restaurant) {
            $activeTaxesQuery = $restaurant->taxes()->where('is_active', true);
            if ($activeTaxesQuery->exists()) {
                $taxRate = $activeTaxesQuery->sum('rate');
                $tax = (int) round($subtotal * ($taxRate / 100));
            } else {
                $tax = (int) ($subtotal * 0.15); // fallback to 15%
            }
        } else {
            $tax = (int) ($subtotal * 0.15);
        }
        $discount = (int) (($data['discount'] ?? 0) * 100);
        $total = max(0, $subtotal + $deliveryFee + $serviceFee + $tax - $discount);

        // Generate unique order number
        $orderNumber = 'SS-'.strtoupper(Str::random(8));
        while (Order::where('order_number', $orderNumber)->exists()) {
            $orderNumber = 'SS-'.strtoupper(Str::random(8));
        }

        $data['order_number'] = $orderNumber;
        $data['subtotal'] = $subtotal;
        $data['delivery_fee'] = $deliveryFee;
        $data['service_fee'] = $serviceFee;
        $data['tax'] = $tax;
        $data['total'] = $total;
        $data['status'] = 'received';
        $data['restaurant_id'] = $restaurant->id;

        if (empty($data['estimated_ready_time']) && $restaurant) {
            $minutes = ($data['type'] ?? '') === 'delivery'
                ? $restaurant->estimated_delivery_time
                : $restaurant->estimated_pickup_time;
            $data['estimated_ready_time'] = now()->addMinutes($minutes ?? 30);
        }

        return $data;
    }
}
