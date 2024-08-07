<p align="center"><img src="resources/images/flashcard-logo.png"></p>

<p align="center">
<img alt="Github Actions" src="https://img.shields.io/badge/version-1.0.0-limegreen?style=plastic">
</p>

# FLASH CARD

A powerful CLI program for managing your flashcards. It gives you the ability to create, manage and practice flashcards. 

## Prerequisites
- Docker
- PHP (>= 8.2)
- Laravel (>= 11.0)
- Redis
- MySQL
- Nginx
- Composer

## Installation
- Clone the repo
- cd into cloned repo
- `cp .env.example .env`
- Fillup all necessary variables in `.env`
- Pull docker images and run containers `docker-compose up --build -d --wait`
- cd into php container `docker exec -it flashcard_php bash`
- Run `composer install`
- Run `php artisan key:generate`
- Run `php artisan migrate`
- Run CLI `php artisan flashcard:interactive`


## BONUS ADDITIONS
### MYSQL 
I opted for MySQL instead of the the default SQLite installation. Makes this powerful and production ready, if necessary. 
### DEDICATED TEST DB 
- For Feature/Unit testing, I'm also using a dedicated MySQL container instead of the traditional in-memory or SQLite DB. It's good to use the same testing engine for both your production and test DB. It encourages consistency.
### UPDATE FLASHCARD 
- Although it was not specified in the instructions, I included the ability to UPDATE FLASHCARDS. I think it's necessary. 
### PAGINATION
- I added the ability to paginate the results. This will be fully embraced by users with hundreds or thousands of flashcards, it gives them the ability to navigate the program. Both during listing, update, create or delete actions. 

There were more things I could have done, but time was not on my side. 

Cheers!

<p align="center">
<img src="resources/images/statistics1.png">
 <img src="resources/images/list.png">
 <img src="resources/images/update.png">
 <img src="resources/images/practice.png">
 
## TESTING
```
php artisan test
```

 
 
