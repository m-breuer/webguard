# WebGuard

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> 💡 **System Architecture Note:** This repository contains the **Management Core & API**. For the distributed scanning node/worker, please visit the [WebGuard Instance Repository](https://github.com/m-breuer/webguard-instance).

WebGuard is a powerful, open-source web monitoring service built with Laravel 12. It's designed to help you track website uptime, response times, and SSL certificate statuses with ease. Whether you're a developer, a small business owner, or a system administrator, WebGuard provides the tools you need to ensure your online services are running smoothly.

The application features a user-friendly dashboard for at-a-glance statistics, a comprehensive admin panel for user and package management, and a REST API for programmatic access and integration with other systems.

## Key Features

* **Uptime Monitoring:** Keep a close eye on your website's availability with asynchronous uptime checks.
* **Response Time Tracking:** Monitor your website's performance by tracking response times.
* **SSL Certificate Monitoring:** Get notified before your SSL certificates expire, so you can renew them in time.
* **Customizable Checks:** Configure HTTP method, body, and headers for your monitoring checks.
* **Real-Time Dashboard:** Visualize your monitoring data with real-time statistics and charts.
* **Admin Panel:** Manage users, subscription packages, and review API usage logs.
* **REST API:** Programmatically access your monitoring data and integrate WebGuard with your existing workflows.
* **Embeddable Widget:** Display your website's monitoring status on external sites with a simple JavaScript widget.
* **Flexible Notifications:** Receive notifications for status changes and SSL expiry via in-app notifications and email.
* **Public Status Pages:** Create public status pages for your monitorings to keep your users informed.

## Core Technologies



### Backend



* **Framework:** Laravel 12 (PHP 8.4) - *Chosen for robust MVC architecture and modern PHP features.*

* **Package Manager:** Composer

*   **API Authentication:** Laravel Sanctum

*   **API Documentation:** Scribe

*   **Social Authentication (Future):** Laravel Socialite - *Installed for future social login integrations, currently configured for GitHub.*

*   **Cache & Queue:** Redis - *Utilized for high-performance caching and efficient queue management for asynchronous monitoring tasks, ensuring minimal latency.*

* **Containerization:** Docker - *Used for production deployment, ensuring a consistent and reproducible environment.*



### Frontend



* **Build Tool:** Vite

* **CSS Framework:** Tailwind CSS

* **JavaScript:**

    * **Reactive Components:** Alpine.js

    * **Data Visualization:** Chart.js

    * **HTTP Requests:** Axios



## Deployment







WebGuard is designed to be deployed using Docker Compose. The provided `docker-compose.prod.yml` file creates a production-ready environment that can be deployed to any Docker-compatible hosting environment.







To deploy the application, run the following command in the root of the project:







```bash



docker-compose -f docker-compose.prod.yml up -d



```







This will start the WebGuard application and all the necessary services. You will need to configure your web server to proxy requests to the `app` service on port `80`.







### CI/CD







You can adapt your CI/CD pipeline to use the `docker-compose.prod.yml` file to deploy the application.



## Local Development (Herd)







For local development, you can use Laravel Herd. Detailed setup instructions can be found in the [Herd Documentation](https://herd.laravel.com/docs).







After cloning the repository, run the following commands to get started:







```bash



composer install



npm install



cp .env.example .env



php artisan key:generate



php artisan migrate --seed



npm run dev



```







Ensure your database and Redis configurations in your `.env` file are set up correctly for your local environment.







## Contributing

We welcome contributions from the community! If you'd like to contribute to WebGuard, please follow these steps:

1.  Fork the repository.
2.  Create a new branch for your feature or bug fix: `git checkout -b feature-or-bugfix-name`.
3.  Make your changes and commit them with a descriptive commit message (adhering to Conventional Commits).
4.  Push your changes to your forked repository.
5.  Create a pull request to the `main` branch of the original repository.

Please make sure to write tests for your changes and ensure that the existing test suite passes.

## License

WebGuard is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
