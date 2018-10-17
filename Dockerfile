# Utilize multi-stage build to keep image size down
FROM composer as composer
COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader --no-progress --no-suggest

# Build the actual image
FROM php

WORKDIR /resume
CMD ["/bin/bash"]

RUN apt-get update \
	&& apt-get install -qqy --no-install-recommends \
        # This is for enabling the program to be run with watch
        procps \
        # Required to run PDF generation
        wget apt-utils libjpeg62-turbo libxrender1 xfonts-75dpi xfonts-base fontconfig libxext6 \
        && apt-get autoremove \
	&& rm -rf /var/lib/apt/lists/*

RUN cd /root \
    && wget https://downloads.wkhtmltopdf.org/0.12/0.12.5/wkhtmltox_0.12.5-1.stretch_amd64.deb --no-verbose \
    && dpkg -i wkhtmltox_0.12.5-1.stretch_amd64.deb

# Enables continously calling a command and piping the output to STDOUT, viewable via docker logs
RUN printf '#!/bin/bash\nwhile sleep 1; do\n    "$@"\ndone' >> /usr/bin/watch-docker \
    && chmod +x /usr/bin/watch-docker

COPY --from=composer /app/vendor /app/vendor
COPY . /app

RUN ln -s /app/bin/md2resume /usr/bin/md2resume
