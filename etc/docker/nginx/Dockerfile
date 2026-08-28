FROM nginx:1.21

RUN rm /etc/nginx/conf.d/default.conf

COPY etc/docker/nginx/conf/app.conf /etc/nginx/conf.d/app.conf
COPY etc/docker/nginx/conf/nginx.conf /etc/nginx/nginx.conf

WORKDIR /app

COPY public/ public/
