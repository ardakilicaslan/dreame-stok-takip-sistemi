# Envantera - Inventory and Sales Management System

Envantera is a modern, PHP-based inventory and sales tracking system designed for small and medium-sized businesses. Easily manage your products, serial numbers, customers, and sales with an intuitive interface.

## 🚀 Features

- **Model and Product Management:** Track product models and their associated serial numbers separately.
- **Stock Tracking:** View real-time stock status (In Stock/Sold) for each serial number.
- **Customer Management:** Store customer information and view customer-specific sales history.
- **Sales Management:** Record which product was sold, when, to which customer, and through which platform.
- **Dynamic Reporting:** Analyze your sales data with visual charts and graphs.
- **User-Friendly Interface:** Modern and intuitive interface built with Tailwind CSS.
- **RESTful API:** API endpoints for products, customers, and analytics data.
- **Modern PHP Architecture:** Built with MVC pattern and OOP principles.

## 🔑 Access

**Default Admin Credentials:**
- **Username:** `admin`
- **Password:** `admin`

## 🗄️ Database Structure

The project includes the following main tables:
- `models` - Product models
- `serial_numbers` - Serial numbers and stock status
- `customers` - Customer information
- `sales` - Sales records
- `platforms` - Sales platforms
- `brands` - Brand information
- `categories` - Product categories

## 🔌 API Usage

The system provides RESTful API endpoints:

- `GET /api/endpoints/products` - Product list
- `GET /api/endpoints/customers` - Customer list
- `GET /api/endpoints/models` - Model list
- `GET /api/endpoints/analytics` - Analytics data

## 🤝 Contributing

1. Fork the project
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 Changelog

### v1.0.0
- Initial release
- Basic inventory management
- Customer and sales tracking
- API endpoints
- Modern PHP architecture

## 📄 License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## 👨‍💻 Developer

**Arda Kılıçaslan** - [GitHub](https://github.com/ardakilicaslan)
