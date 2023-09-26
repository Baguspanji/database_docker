<?php
function adminer_object() {
    // required to run any plugin
    include_once "./plugins/plugin.php";
    
    // autoloader
    foreach (glob("plugins/*.php") as $filename) {
        include_once "./$filename";
    }
    
    // enable extra drivers just by including them
    //~ include "./plugins/drivers/simpledb.php";
    
    $plugins = array(
        // specify enabled plugins here
        
        new AdminerDatabaseHide(array('information_schema', 'performance_schema', 'sys')),
        new AdminerTablesFilter(),
        new AdminerSimpleMenu(),
        new AdminerCollations(),
        new AdminerJsonPreview(),
        // new AdminerTheme('lukashron'),
        new AdminerTheme('default-blue'),
        // new AdminerTableHeaderScroll(),
        // new AdminerTreeViewer('./plugins/tree-viewer.js'),
        // new AllowEmptyPasswordPlugin(),
        
        new AdminerSqlLog('./logs/sql-log.sql'),
        new OneClickLogin(include './server.php'),
    );
    
    /* It is possible to combine customization and plugins:
    class AdminerCustomization extends AdminerPlugin {
    }
    return new AdminerCustomization($plugins);
    */
    
    return new AdminerPlugin($plugins);
}

// include original Adminer or Adminer Editor
include "./adminer.php";
