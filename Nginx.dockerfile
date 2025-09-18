FROM nginx:stable-alpine

# Copie le fichier de configuration Nginx
COPY ./Nginx.conf /etc/nginx/conf.d/default.conf

# Copie les fichiers de l'application dans le répertoire par défaut
COPY . /var/www/html/

# Expose le port 80 pour le trafic web
EXPOSE 80

# Démarre Nginx au premier plan
CMD ["nginx", "-g", "daemon off;"]
