#!/bin/bash

DEPLOY_HOST=your_host
DEPLOY_USER=your_host_user
DEPLOY_PATH=/home/yoursite
DEPLOY_CURRENT_PATH=/home/yoursite/public
DEPLOY_BUILD=$$
DEPLOY_COMMIT=$$
DEPLOY_BRANCH=dev
DEPLOY_CLONE_DIR=./

envoy run deploy --host=$DEPLOY_HOST --user=$DEPLOY_USER --deploy_path=$DEPLOY_PATH --build=$DEPLOY_BUILD --commit=$DEPLOY_COMMIT --branch=$DEPLOY_BRANCH --php=php --local_dir=$DEPLOY_CLONE_DIR --current_path=$DEPLOY_CURRENT_PATH