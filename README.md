# Paragliding Booking Application

This application allows users to request paragliding flights and administrators to manage these requests and pilots.

## Docker Setup

This application can be run using Docker and Docker Compose.

### Prerequisites

- Docker: [Install Docker](https://docs.docker.com/get-docker/)
- Docker Compose: [Install Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop)

### Running the Application

1.  **Clone the repository (if you haven't already):**
    ```bash
    git clone <repository-url>
    cd <repository-directory>
    ```

2.  **Build and Start the Docker Containers:**
    Open a terminal in the project root directory (where `docker-compose.yml` is located) and run:
    ```bash
    docker-compose up --build -d
    ```
    - `--build`: Forces Docker to rebuild the images if there are changes to the `Dockerfile` or application code.
    - `-d`: Runs the containers in detached mode (in the background).

3.  **Access the Application:**
    Once the containers are up and running, you can access the application in your web browser at:
    [http://localhost:8080](http://localhost:8080)

    The `BASE_URL` in `config/config.php` is set to `http://localhost/paragliding_booking/public`. The Docker setup maps port 8080 on your host to port 80 in the `app` container. The Apache configuration inside the container serves the application from `/var/www/html/paragliding_booking/public`.

4.  **Database Initialization:**
    The database schema defined in `paragliding_booking/sql/schema.sql` is automatically imported when the `db` container starts for the first time. The database will be named `paragliding_db` with user `db_user` and password `db_password` as configured in `docker-compose.yml` and `config/config.php`.

    If you need to access the database directly from your host machine (e.g., using a MySQL client like DBeaver or MySQL Workbench), you can connect to:
    - Host: `127.0.0.1` or `localhost`
    - Port: `33069` (as mapped in `docker-compose.yml`)
    - Username: `root` (or `db_user`)
    - Password: `root_password` (or `db_password`)

5.  **Stopping the Application:**
    To stop the Docker containers, run:
    ```bash
    docker-compose down
    ```
    This will stop and remove the containers. The database data will persist in the `mysql_data` volume if you want to keep it. To remove the volume as well (and lose all database data), you can run:
    ```bash
    docker-compose down -v
    ```

### Development Notes

-   **Code Changes:** The application code ( `config/` and `paragliding_booking/`) is mounted as a volume into the `app` container. This means any changes you make to the code on your host machine will be reflected immediately in the running container, so you don't need to rebuild the image for every code change (Apache/PHP should pick up PHP file changes automatically).
-   **PHP Extensions:** If you need additional PHP extensions, add them to the `Dockerfile` and rebuild the image using `docker-compose build app` or `docker-compose up --build -d app`.
-   **Configuration:**
    -   Application configuration is in `config/config.php`.
    -   Docker-specific configurations (ports, volumes, environment variables) are in `docker-compose.yml`.
    -   Database credentials for the Docker environment are set in `docker-compose.yml` and read by `config/config.php` via environment variables.
-   **Logs:** To view logs from the containers:
    ```bash
    docker-compose logs app
    docker-compose logs db
    ```
    To follow the logs in real-time:
    ```bash
    docker-compose logs -f app
    ```

### Troubleshooting

-   **Port Conflicts:** If port `8080` or `33069` is already in use on your host machine, you can change the host-side port mapping in `docker-compose.yml`. For example, change `8080:80` to `8081:80` for the application.
-   **`BASE_URL`:** Ensure the `BASE_URL` in `config/config.php` correctly reflects how you access the application. For this Docker setup, `http://localhost/paragliding_booking/public` is used internally by the app for URL generation, and the `localhost:8080` mapping handles external access. If you change host port or domain, this might need adjustment, or preferably, the app should be made more environment-aware regarding its base URL.
-   **Permissions:** If you encounter file permission issues (e.g., for cache or log directories if the app creates them), you might need to adjust permissions in the `Dockerfile` or ensure the user running Apache (`www-data`) has write access.

This README provides a basic guide. Further details on the application's functionality can be found by exploring the code.
