<?php


abstract class flatdbase_config
{
    // @const allow only database names with less than 16 chars
    const max_dbname_size = 16;
    const max_tablename_size = 16;
    const database_folder_name = "db" ;
    const ext_data       = ".dff" ;
    const ext_header     = ".hff" ;
    const encrypt_method = "AES-256-CBC";
    const encryption_key = "o@21sHDiWaNDnS#446175s20160501";
    const encryption_iv  = "o@21sHDiWaNDnS#446175s20160501iv";
}

