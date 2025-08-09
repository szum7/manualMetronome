-- Create database (change name as you wish)
CREATE DATABASE IF NOT EXISTS clock_sync CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clock_sync;

-- Stored timestamps: each client's timestamp for a synchronization attempt
CREATE TABLE IF NOT EXISTS sync_timestamps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  synchid INT NOT NULL,
  client_id VARCHAR(128) NOT NULL,
  ts_usec BIGINT NOT NULL, -- microseconds since epoch
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Offsets computed per synch attempt
CREATE TABLE IF NOT EXISTS sync_offsets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  synchid INT NOT NULL,
  client_id VARCHAR(128) NOT NULL,
  offset_usec BIGINT NOT NULL, -- signed microseconds: client_ts - ref_ts
  ref_client_id VARCHAR(128) NOT NULL,
  server_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional: store blink start time for each synch attempt
CREATE TABLE IF NOT EXISTS sync_session (
  synchid INT PRIMARY KEY,
  ref_client_id VARCHAR(128) NOT NULL,
  blink_start_usec BIGINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);