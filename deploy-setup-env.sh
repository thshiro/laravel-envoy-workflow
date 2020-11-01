#!/bin/bash

touch src/.env
echo "APP_NAME=mevisite" >> src/.env
echo "APP_ENV=$APP_ENV" >> src/.env
echo "APP_KEY=" >> src/.env
echo "APP_URL=https://mevisite.com" >> src/.env
echo "DEBUG=$DEBUG" >> src/.env

echo "DB_CONNECTION=mysql" >> src/.env
echo "DB_HOST=localhost" >> src/.env
echo "DB_PORT=3306" >> src/.env
echo "DB_USERNAME=$DB_USERNAME" >> src/.env
echo "DB_PASSWORD=$DB_PASSWORD" >> src/.env