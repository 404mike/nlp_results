<?php

$db = new SQLite3('amp.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

// Create a table.
$db->query('CREATE TABLE IF NOT EXISTS "queue"(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "project" INTEGER,
    "query_type" VARCHAR,
    "query" VARCHAR,
    "status" VARCHAR
)');

$db->close();