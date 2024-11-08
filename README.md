# Druid Backend powered by Drupal
=====================================
 
This app is developed based on the existing Druid website using our on code, design and mautic integration with drupal

## Table of Contents

- [Druid Backend powered by Drupal](#druid-backend-powered-by-drupal)
  - [Table of Contents](#table-of-contents)
    - [Features](#features)
    - [Tech stacks](#tech-stacks)
    - [prerequisites](#prerequisites)
    - [Getting started](#getting-started)
    - [Configration](#configration)
    - [API Documentation](#api-documentation)


### Features
 
 - Core functionalities of the backend
      - Integration with mautic
      - Data validation and error handling
  

  ### Tech stacks 

   - Backend: Drupal
   - Database:
  
  ### prerequisites
   
   - Drupal: 11
   - Database:
   - A `.env`` file with necssary enviroment variables
  
  ### Getting started

  1. Clone the repository
      ```
      git@github.com:Druid-Project/druid-backend.git
      ```
  2. Install dependencies 
      ```
      lando composer install
      ``` 
  3. Set up environment Variables
  4. Start the server
     - For development
     ```
     lando start
     ``` 
     - For production
      ```
      lando info
      ```
   The server should be running at

   `https://localhost:57312`
    
### Configration
    
    


### API Documentation

The API documentation is available once the server is running, you can access it at:
  
  ```
  https://localhost:57312/api
  ```

  




