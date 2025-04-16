@echo off
echo Creating Spice POS folder structure...

REM Set the root directory name
set ROOT_DIR=spice-pos

REM Create the root directory if it doesn't exist
if not exist "%ROOT_DIR%" (
    mkdir "%ROOT_DIR%"
    echo Created directory: %ROOT_DIR%
) else (
    echo Directory %ROOT_DIR% already exists. Skipping creation.
)

REM Change into the root directory
cd "%ROOT_DIR%"

REM Create top-level PHP files
echo. > index.php
echo. > products.php
echo. > reports.php
echo. > process_sale.php
echo. > manage_product.php
echo Created top-level PHP files.

REM Create css directory and file
if not exist "css" mkdir css
echo. > css\style.css
echo Created css directory and style.css.

REM Create js directory and file
if not exist "js" mkdir js
echo. > js\app.js
echo Created js directory and app.js.

REM Create lib directory and files
if not exist "lib" mkdir lib
echo. > lib\data_handler.php
echo. > lib\products_db.php
echo. > lib\sales_db.php
echo. > lib\helpers.php
echo Created lib directory and helper PHP files.

REM Create data directory and files with initial content
if not exist "data" mkdir data
echo [] > data\products.json
echo [] > data\sales.json
echo Deny from all > data\.htaccess
echo Created data directory and initial data/security files.

REM Create templates directory and files
if not exist "templates" mkdir templates
echo. > templates\header.php
echo. > templates\footer.php
echo Created templates directory and files.

REM Go back to the original directory
cd ..

echo Spice POS structure created successfully in '%ROOT_DIR%' directory!
echo IMPORTANT: Ensure your web server has WRITE permissions for the '%ROOT_DIR%\data' directory.
echo IMPORTANT: The '.htaccess' file in 'data' helps protect it on Apache servers. Configure Nginx separately if needed.

REM Pause to see the output before the window closes
pause