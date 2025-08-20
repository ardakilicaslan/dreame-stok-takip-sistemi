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

## 🛠️ Installation

Follow these steps to run the project on your local machine.

#### Requirements
- [XAMPP](https://www.apachefriends.org/tr/index.html) or similar local server (Apache, MySQL, PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher

#### Steps

1. **Download the Project:** Download this repo as ZIP or clone it. Place the files in your XAMPP installation's `htdocs` folder as `Envantera`.

2. **Create Database:**
   - Open `phpMyAdmin`
   - Create a new database named `envantera`
   - Import the `stoksayim.sql` file

3. **Configure Database Connection:**
   - Open `config/Config.php`
   - Update your database information:
     ```php
     'DB_HOST' => 'localhost',
     'DB_NAME' => 'envantera',
     'DB_USER' => 'root',
     'DB_PASS' => 'your_password'
     ```

4. **Run the Application:**
   - Navigate to `http://localhost/Envantera` in your browser
   - Default admin credentials:
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

## 🚀 Deployment

### Production Environment
1. Set `APP_ENV` to `production` in `config/Config.php`
2. Set `APP_DEBUG` to `false`
3. Review security settings
4. Optimize web server configuration

## 🛡️ Security Features

- CSRF token protection
- Rate limiting (for login attempts)
- Input validation and sanitization
- SQL injection protection
- XSS protection
- Session security

## 📊 Performance

- Database query optimization
- Caching support
- Image optimization
- Lazy loading

## 🐛 Troubleshooting

### Common Issues

1. **Database connection error:**
   - Ensure MySQL service is running
   - Check database credentials

2. **File upload error:**
   - Check write permissions for `img/uploads` folder
   - Check PHP upload limits

3. **Session error:**
   - Run `clear_session.php` file

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

## 📞 Support

If you encounter any issues:
- [Create an issue](https://github.com/ardakilicaslan/envantera/issues)
- [Check the wiki](https://github.com/ardakilicaslan/envantera/wiki)
