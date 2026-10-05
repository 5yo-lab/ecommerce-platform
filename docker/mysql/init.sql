SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Ensure the application user can manage the shared schema
GRANT ALL PRIVILEGES ON ecommerce.* TO 'ecommerce'@'%';
FLUSH PRIVILEGES;
