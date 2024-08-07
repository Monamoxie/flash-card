<p align="center"><img src="resources/images/flashcard-logo.png"></p>

<p align="center">
<img alt="Github Actions" src="https://img.shields.io/badge/version-1.0.0-limegreen?style=plastic">
</p>

# FLASH CARD

A powerful command line application for managing and practicing flash cards. Written in PHP with CLI authentication capabilities, it is portable, fully dockerised and easy to setup. 

## Tech Stack
- PHP 8 <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/php/php-original.svg" height="40" title="PHP" alt="PHP" width="25" height="10"/>
- Docker <img src="https://techstack-generator.vercel.app/docker-icon.svg" title="docker" alt="docker" width="20" height="20"/> 
- Laravel  <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/laravel/laravel-original.svg" title="Laravel" alt="Laravel" width="20" height="20"/> 
- Redis <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/redis/redis-original-wordmark.svg" title="redis" alt="redis" width="20" height="20"> 
- MySQL <img src="https://techstack-generator.vercel.app/mysql-icon.svg" title="MySQL" alt="MySQL"  width="20" height="20"/>
- Nginx <img src="https://techstack-generator.vercel.app/nginx-icon.svg" title="nginx" alt="nginx"  width="20" height="20"/>


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
A MySQL DB instead of the the default SQLite installation provided by Laravel out of the box. Makes this powerful and production ready, if necessary. 
### DEDICATED TEST DB 
- For Feature/Unit testing, a dedicated MySQL container instead of the traditional in-memory or SQLite DB. It's good to use the same testing engine for both your production and test DB. It encourages consistency.
### PAGINATION
- Ability to paginate the results. This will be fully embraced by users with hundreds or thousands of flashcards, it gives the ability to navigate the program, either during listing, update, create or delete actions. 

<p align="center">
<img src="resources/images/statistics1.png">
 <img src="resources/images/list.png">
 <img src="resources/images/update.png">
 <img src="resources/images/practice.png">
 <img src="resources/images/test.png">
 
## TESTING
```
php artisan test
```

## LICENSE
[MIT license](https://opensource.org/licenses/MIT)