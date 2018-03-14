# Utilize multi-stage build to keep image size down
FROM composer as composer
COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader --no-progress --no-suggest

# Build the actual image
FROM php

RUN apt-get update \
	&& apt-get install -qqy --no-install-recommends\
        # This is for enabling the program to be run with watch
        procps \
        wkhtmltopdf \
        # Required to run PDF generation
        xvfb \
        xauth \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer /app/vendor /app/vendor
COPY . /app

RUN ln -s /app/bin/md2resume /usr/bin/md2resume

RUN echo "alias md2pdf=\"xvfb-run md2resume pdf\"" >> ~/.bashrc

WORKDIR /resume
