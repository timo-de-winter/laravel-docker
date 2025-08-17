FROM ubuntu:24.04

LABEL maintainer="Timo de Winter"

ARG NODE_VERSION=22
ARG MYSQL_CLIENT="mariadb-client"
ARG USER_ID=1001
ARG GROUP_ID=1001

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# This is used to run the application
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_QUEUE_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan horizon"
ENV SUPERVISOR_BOOST_MCP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan boost:mcp"
ENV SUPERVISOR_BOOST_MCP_AUTOSTART="false"
ENV SUPERVISOR_PHP_USER="appuser"

# Setup server timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install and setup everything required to run the application
RUN apt-get update && apt-get upgrade -y && \
    mkdir -p /etc/apt/keyrings && \
    apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano && \
    curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null && \
    echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list && \
    apt-get update && \
    apt-get install -y php8.4-cli php8.4-dev \
        php8.4-gd php8.4-curl php8.4-imap php8.4-mysql \
        php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath \
        php8.4-soap php8.4-intl php8.4-readline php8.4-ldap \
        php8.4-msgpack php8.4-igbinary php8.4-redis php8.4-memcached \
        php8.4-pcov php8.4-imagick && \
    curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list && \
    apt-get update && \
    apt-get install -y nodejs && \
    npm install -g npm && \
    apt-get install -y $MYSQL_CLIENT && \
    apt-get -y autoremove && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Set capabilities
RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.4

# Create user and group
RUN groupadd -g ${GROUP_ID} appuser && \
    useradd -u ${USER_ID} -g appuser -m -s /bin/bash appuser

# Create and chown .composer *as root*
RUN mkdir -p /.composer && chown appuser:appuser /.composer

# Set WORKDIR
WORKDIR /var/www/html

# Set node memory size
RUN export NODE_OPTIONS=--max-old-space-size=4096
ENV NODE_OPTIONS="--max-old-space-size=4096"

# Switch to appuser
USER appuser

# Copy files *as appuser*
COPY --chown=appuser:appuser . /var/www/html

# Switch back to root for final setup
USER root
COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.4/cli/conf.d/99-timodewinter-laravel-docker.ini
RUN chmod +x /usr/local/bin/start-container

#EXPOSE 80/tcp
EXPOSE 80

ENTRYPOINT ["start-container"]
