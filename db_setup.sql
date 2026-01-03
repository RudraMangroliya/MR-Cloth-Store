DROP DATABASE IF EXISTS `mr_cloth_store`;
-- Create the database
CREATE DATABASE IF NOT EXISTS `mr_cloth_store`;

USE `mr_cloth_store`;

-- Table structure for users
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `birthday` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for products
CREATE TABLE `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample products with image URLs
-- Make sure you have an 'img' folder with these files
INSERT INTO `products` (`id`, `name`, `price`, `category`, `image_url`) VALUES
(1, 'Classic T-Shirt', 2549.00, 'casual', 'img/Classic T-Shirt.jpg'),
(2, 'Denim Jacket', 7649.00, 'outerwear', 'img/Denim Jacket.jpg'),
(3, 'Summer Dress', 5099.00, 'dresses', 'img/Summer Dress.avif'),
(4, 'Casual Jeans', 4249.00, 'casual', 'img/Casual Jeans.jpg'),
(5, 'Hoodie', 3399.00, 'casual', 'img/Hoodie.webp'),
(6, 'Blazer', 11049.00, 'formal', 'img/Blazer.jpg'),
(7, 'Sneakers', 6799.00, 'shoes', 'img/Sneakers.webp'),
(8, 'Winter Coat', 16999.00, 'outerwear', 'img/Winter Coat.avif'),
(9, 'Kurta', 3000.00, 'formal', 'img/Kurta.jpg'),
(10, 'Leather Jacket', 10000.00, 'outerwear', 'img/Leather Jacket.webp'),
(11, 'Formal Shoes', 12499.00, 'shoes', 'img/Formal Shoes.avif'),
(12, 'Cargo Pants', 5449.00, 'casual', 'img/Cargo Pants.webp'),
(13, 'Chinos', 5949.00, 'casual', 'img/Chinos.webp'),
(14, 'Henley Shirt', 8749.00, 'casual', 'img/Henley Shirt.jpg'),
(15, 'Joggers', 4349.00, 'casual', 'img/Joggers.avif'),
(16, 'Lymio T-shirt', 8300.00, 'casual', 'img/Lymio T-shirt.jpg'),
(17, 'Polo T-shirt', 6500.00, 'casual', 'img/Polo T-shirt.jpg'),
(18, 'Sweatshirt', 4999.00, 'casual', 'img/Sweatshirt.webp'),
(19, 'Bomber Jacket', 19999.00, 'outerwear', 'img/Bomber Jacket.webp'),
(20, 'Windbreaker', 8999.00, 'outerwear', 'img/Windbreaker.jpg'),
(21, 'Overshirt', 7999.00, 'outerwear', 'img/Overshirt.webp'),
(22, 'Casual Dress', 199.00, 'dresses', 'img/Casual Dress.jpg'),
(23, 'Maxi Dress', 299.00, 'dresses', 'img/Maxi Dress.webp'),
(24, 'Sneakers', 9799.00, 'shoes', 'img/Loafers.webp'),
(25, 'Slip-ons', 12799.00, 'shoes', 'img/Slip-ons.jpg'),
(26, 'Sandals', 6999.00, 'shoes', 'img/Sandals.webp'),
(27, 'Flip-flops', 9999.00, 'shoes', 'img/Flip-flops.webp'),
(28, 'Formal Shirt', 5049.00, 'formal', 'img/Formal Shirt.webp'),
(29, 'Dress Pants', 6549.00, 'formal', 'img/Dress Pants.webp'),
(30, 'Suit (2-piece or 3-piece)', 21549.00, 'formal', 'img/Suit (2-piece or 3-piece).jpg'),
(31, 'Waistcoat', 9549.00, 'formal', 'img/Waistcoat.webp'),
(32, 'Bow Tie', 2049.00, 'formal', 'img/Bow tie.jpg'),
(33, 'Dress Belt', 4449.00, 'formal', 'img/Dress belt.webp'),
(34, 'Trench Coat', 13999.00, 'formal', 'img/Trench coat.jpg'),
(35, 'Overcoat', 16499.00, 'formal', 'img/Overcoat.jpeg'),
(36, 'Suit Jacket', 12399.00, 'formal', 'img/Suit Jacket.webp');

-- Table structure for orders
CREATE TABLE `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `total` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for order items
CREATE TABLE `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` INT(11) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for cart
CREATE TABLE `cart` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


SELECT * FROM users;
SELECT * FROM products;