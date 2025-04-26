# 📱 D7 Network SMS Gateway for WooCommerce

Send SMS notifications to Admin and Customers on WooCommerce order status changes using D7 Networks API.

---

## ✨ Features

- Secure Access Token authentication (hidden field)
- Per-status SMS templates (for Admin and Customer separately)
- Enable/Disable notifications per order status
- Send Test SMS directly from settings page
- View SMS Logs with Phone, Message, Status, Timestamp, Recipient (admin/customer)
- Database table auto-created on plugin activation
- Safe handling for old SMS logs (no warnings)

---

## 📚 Placeholders Available

In the SMS templates, you can use:
- `{site_title}` - Website title
- `{order_id}` - Order ID
- `{order_status}` - WooCommerce Order Status
- `{order_total}` - Total order amount
- `{billing_name}` - Customer’s billing name
- `{shipping_name}` - Customer’s shipping name
- `{shipping_method}` - Shipping method
- `{additional_notes}` - Customer notes
- `{order_date}` - Order date

---

## 🔧 Installation

1. Upload the plugin files to the `/wp-content/plugins/d7-network-sms-gateway/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **D7 SMS Settings** and configure:
    - Access Token
    - Sender ID
    - Admin Phone numbers
    - Templates (Customer and Admin per-status)

---

## 🖼️ Screenshots

- **Settings Page**  
- **SMS Logs Page**  
- **Send Test SMS Form**

---

## 🚀 Planned Features (Coming Soon)

- Export SMS Logs to CSV
- Multi-language SMS support (English / Arabic)
- SMS Retry on failure
- Auto Cleanup of Old Logs

---

## 📝 License

This project is licensed under the MIT License.

---

## 📦 Built With

- WordPress
- WooCommerce
- D7 Networks API
