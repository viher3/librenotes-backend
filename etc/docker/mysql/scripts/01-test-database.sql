-- Executed by the MySQL image on first start (empty data volume only).
-- Gives the application user access to the test database used by `make tests/*`.
CREATE DATABASE IF NOT EXISTS `symfony-ddd-core_test`;
GRANT ALL PRIVILEGES ON `symfony-ddd-core_test`.* TO 'core'@'%';
FLUSH PRIVILEGES;
