CREATE DATABASE IF NOT EXISTS clock_sync CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clock_sync;

CREATE TABLE IF NOT EXISTS sync_timestamps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sync_id INT NOT NULL,
  client_id VARCHAR(128) NOT NULL,
  same_time_ts_usec BIGINT NOT NULL, -- same time timestamp, usec
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sync_offsets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sync_id INT NOT NULL,
  client_id VARCHAR(128) NOT NULL,
  offset_usec BIGINT NOT NULL, -- offset from the reference client
  ref_client_id VARCHAR(128) NOT NULL,
  blink_start_usec BIGINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);