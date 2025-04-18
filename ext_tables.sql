CREATE TABLE sys_file_processedfile (
	`integration` VARCHAR(32),
	`integration_checksum` VARCHAR(40),

	key `integration` ( `integration` ),
        key `integration_checksum` ( `integration_checksum` )
);
