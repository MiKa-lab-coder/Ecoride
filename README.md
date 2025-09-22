# Ecoride
ECF Graduate Project - 2025

# Project description
Ecoride is a web application of carpooling for daily commutes.
It allows users to share rides, reduce travel costs, and minimize environmental impact.
The platform connects drivers with passengers heading in the same direction, promoting eco-friendly transportation options.

# Technologies used (full scratch development)
- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL, MongoDB
- Web server: Nginx
- Containerization: Docker

# local deployment with docker
# Prerequisites
- install docker desktop on your machine
- make sure docker is running
- make sure you have git installed on your machine

# Deployment steps
- clone repository on your local machine with git clone https://github.com/MiKa-lab-coder/Ecoride.git
- open the project with your favorite IDE (Visual Studio Code, PhpStorm, etc.)
- change in the docker-compose.yml file:
 MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
 MYSQL_DATABASE: ${MYSQL_DATABASE}
 MYSQL_USER: ${MYSQL_USER}
 MYSQL_PASSWORD: ${MYSQL_PASSWORD} 
 and :     
 MONGO_INITDB_ROOT_USERNAME: ${MONGO_INITDB_ROOT_USERNAME}
 MONGO_INITDB_ROOT_PASSWORD: ${MONGO_INITDB_ROOT_PASSWORD}
 with your own values for the database connection (for a test deployment you can use brute values like test, test1234, etc.
- but for a production deployment you have to use strong values use .env file to store your environment variables)
- open a terminal in the project folder and run the command: docker-compose up -d --build
- wait for the containers to be created and started
- open your browser and go to http://localhost:8000 to access the application

# Notes
- you can also access to the project with docker desktop interface. You will see 4 containers running:
  - ecoride-nginx-1
  - ecoride-php
  - ecoride_db
  - ecoride_mongo
- you just have to click the link 8000:80 -> to access the application
- to stop the containers, run the command: docker-compose down
- to restart the containers, run the command: docker-compose up -d