FROM php:7.4-apache
RUN git clone git@github.com:Anes-BHD/library.git
RUN cd library && git checkout stage
COPY . /var/www/html/
EXPOSE 80

