sudo update-alternatives --set php /usr/bin/php8.1
sudo systemctl start mysql
export NODE_OPTIONS=--max_old_space_size=8192
code . 
gnome-terminal --tab --title="HTTP-Server"      -- php artisan serve
gnome-terminal --tab --title="JS Compiler"      -- npm run hot
gnome-terminal --tab --title="Konsole"    		-- php artisan tinker
