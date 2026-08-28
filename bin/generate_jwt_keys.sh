echo "Generating JWT keys..."
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out /app/config/jwt/private.pem 2>/dev/null
openssl rsa -in /app/config/jwt/private.pem -pubout -out /app/config/jwt/public.pem 2>/dev/null
chown www-data:www-data /app/config/jwt/*.pem
echo "JWT keys generated."