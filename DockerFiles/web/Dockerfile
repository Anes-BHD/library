FROM nginx:alpine

# Install optional tools (nginx already installed)
RUN apk update && apk add --no-cache bash

# Remove default config
RUN rm /etc/nginx/conf.d/default.conf

# Copy custom config
COPY library.conf /etc/nginx/conf.d/app.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
