FROM composer:latest

# to experiment remote storage of public content
RUN curl https://rclone.org/install.sh | bash

RUN mkdir -p /opt/satis-gitlab
WORKDIR /opt/satis-gitlab
COPY composer.json .
COPY composer.lock .
RUN composer install

COPY . /opt/satis-gitlab
WORKDIR /opt/satis-gitlab
