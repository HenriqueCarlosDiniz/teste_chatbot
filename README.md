# Chatbot Project

This is a chatbot application built with Laravel. It is designed to handle conversations with users, understand their intentions, and perform actions based on their requests. The chatbot is capable of handling appointment bookings, checking existing appointments, and engaging in general conversation.

## Features

- **Conversational Interface**: Engage in natural conversations with users.
- **Intent Recognition**: Understand the user's intent (e.g., booking, inquiry, greeting).
- **Appointment Booking**: Schedule new appointments for users.
- **Existing Appointment Management**: Check, modify, or cancel existing appointments.
- **Multi-platform**: Can be integrated with web and WhatsApp.
- **Extensible**: New functionalities can be easily added.

## Technologies

- **Backend**: Laravel 12
- **Database**: MySQL 8.0
- **Caching**: Redis 6.2
- **Web Server**: Nginx 1.19
- **Containerization**: Docker
- **Real-time Communication**: Laravel Reverb
- **Frontend (for chat interface)**: Streamlit

## Installation

1. **Clone the repository:**
   ```sh
   git clone https://github.com/your-username/your-repository.git
   cd your-repository
   ```

2. **Create the `.env` file:**
   ```sh
   cp .env.example .env
   ```
   > **Note:** You may need to update the environment variables in the `.env` file to match your local setup.

3. **Build and run the Docker containers:**
   ```sh
   docker-compose up -d --build
   ```

4. **Install Composer dependencies:**
   ```sh
   docker-compose exec app composer install
   ```

5. **Generate the application key:**
   ```sh
   docker-compose exec app php artisan key:generate
   ```

6. **Run database migrations:**
   ```sh
   docker-compose exec app php artisan migrate
   ```

## Services

The `docker-compose.yml` file defines the following services:

- **app**: The main Laravel application container.
- **nginx**: The Nginx web server that serves the application.
- **db**: The MySQL database container.
- **redis**: The Redis container for caching and queueing.
- **reverb**: The Laravel Reverb server for real-time WebSocket communication.
- **worker**: A dedicated container to run the Laravel queue worker.
- **streamlit**: A container for the Streamlit chat interface.
