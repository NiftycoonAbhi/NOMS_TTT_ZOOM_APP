@echo off
echo ========================================
echo TTT ZOOM Database Quick Setup
echo ========================================
echo.

echo Checking if XAMPP MySQL is running...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✓ MySQL service is running
) else (
    echo ✗ MySQL service is not running
    echo Please start MySQL in XAMPP Control Panel first
    echo.
    pause
    exit /b 1
)

echo.
echo Creating database 'ttt_zoom_system'...
echo.

cd /d "C:\xampp\mysql\bin"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ttt_zoom_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

if %ERRORLEVEL% EQU 0 (
    echo ✓ Database created successfully
    echo.
    echo Importing database schema...
    mysql -u root ttt_zoom_system < "C:\xampp\htdocs\NOMS_TTT_ZOOM_APP\TTT_NOMS_ZOOM\database\ttt_zoom_complete.sql"
    
    if %ERRORLEVEL% EQU 0 (
        echo ✓ Database schema imported successfully
        echo.
        echo ========================================
        echo Database setup completed!
        echo You can now access the TTT ZOOM system at:
        echo http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/
        echo ========================================
    ) else (
        echo ✗ Error importing database schema
        echo Please check the SQL file exists and try again
    )
) else (
    echo ✗ Error creating database
    echo Please check MySQL is running and try again
)

echo.
pause
