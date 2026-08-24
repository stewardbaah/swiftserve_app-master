# SwiftServe User Walkthroughs & Flows

This document outlines the standard user journeys and workflows within the SwiftServe application, separated by user roles: **Customers** and **Merchants**.

---

## 1. Customer Flows (Storefront)

The storefront is designed to be highly responsive and intuitive, ensuring a frictionless shopping experience.

### Scenario A: Browsing and Purchasing a Product (Guest/New User)

1.  **Landing Page:** The customer arrives at the homepage and browses featured categories and products.
2.  **Product Details:** The customer clicks on a product to view its details, select options (e.g., size, color), and clicks **"Add to Cart"**. A non-intrusive toast notification confirms the addition.
3.  **Cart Review:** The customer opens the slide-out cart drawer or navigates to the dedicated cart page to review their items.
4.  **Initiate Checkout:** The customer clicks **"Proceed to Checkout"**.
5.  **Authentication/Guest Checkout:** 
    *   Since they are not logged in, they are prompted to either create an account, log in, or continue as a guest (if enabled).
    *   *Flow:* Customer chooses to register. They fill out their email, password, and basic details. Laravel Fortify handles the secure registration in the background.
6.  **Shipping Details:** The customer enters their shipping address.
7.  **Payment:** The customer selects their payment method and completes the transaction.
8.  **Order Confirmation:** An order success page is displayed with the order reference number, and an email receipt is dispatched.

### Scenario B: Customer Dashboard Management

1.  **Login:** A returning customer clicks "Login" and authenticates using their credentials.
2.  **Dashboard Access:** Upon login, they are redirected to their personal dashboard.
3.  **Order History:** The customer navigates to the "My Orders" tab to view past purchases, check fulfillment status, and download invoices.
4.  **Profile Settings:** In the settings tab, the customer updates their default shipping address or changes their password securely.

---

## 2. Merchant Flows (Filament Portal)

The merchant portal is a secure, restricted area intended for store owners and administrators to manage the business.

### Scenario A: Adding a New Product to the Store

1.  **Portal Login:** The merchant navigates to the admin route (e.g., `/admin`) and logs in with their administrative credentials.
2.  **Dashboard Overview:** The merchant lands on the Filament dashboard, viewing high-level metrics like recent sales, active users, and low-stock warnings.
3.  **Navigate to Products:** The merchant clicks the "Products" resource link in the sidebar.
4.  **Create Product:** The merchant clicks "New Product" and fills out the comprehensive form:
    *   **Basic Info:** Title, description, and base price.
    *   **Media:** Uploads product images.
    *   **Inventory:** Sets the SKU and current stock quantity.
    *   **Categorization:** Assigns the product to a specific category or collection.
5.  **Publish:** The merchant saves the product. It is immediately available on the customer-facing Vue storefront.

### Scenario B: Processing an Order

1.  **Order Notification:** The merchant logs into the portal and sees a new unfulfilled order on their dashboard.
2.  **View Order Details:** The merchant navigates to the "Orders" resource and clicks on the newly placed order to view the customer's shipping details and purchased items.
3.  **Fulfillment Action:** 
    *   The merchant prepares the physical package.
    *   In the portal, they update the order status from *Processing* to *Shipped*.
    *   They input the tracking number into the order record.
4.  **Customer Update:** Updating the status automatically triggers an email notification to the customer with their tracking details.

### Scenario C: Reviewing Analytics

1.  **Access Widgets:** On the main admin dashboard, the merchant reviews the Filament chart widgets.
2.  **Sales Data:** They filter the sales chart to view the past 30 days of revenue to track the success of a recent marketing campaign.
3.  **Top Products:** They review the "Best Selling Products" table widget to decide which items need to be restocked soon.
