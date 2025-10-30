use gogograbs;
ALTER TABLE transactions
ADD COLUMN price DECIMAL(10,2) NOT NULL AFTER quantity;
