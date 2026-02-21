#!/bin/bash

echo "=== Import Maruba Database Data ==="
echo ""
echo "This script will import the complete database with sample data."
echo "Please enter your MySQL root password when prompted."
echo ""

# Import the complete database structure and data
echo "Importing database structure and sample data..."
mysql -u root -p maruba < sql/maruba.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "=== Import Successful! ==="
    echo ""
    echo "Sample data included:"
    echo "- 5 users with different roles (admin, kasir, teller, surveyor, collector)"
    echo "- 5 members with geo-coordinates"
    echo "- 7 loan/savings products"
    echo "- 4 loan applications with various statuses"
    echo "- 4 survey records with geo-coordinates"
    echo "- 5 repayment records"
    echo "- 11 loan documents"
    echo "- 6 audit logs"
    echo ""
    echo "Default login credentials:"
    echo "Username: admin"
    echo "Password: admin123"
    echo ""
    echo "Access the application at: http://localhost/maruba/login"
else
    echo ""
    echo "=== Import Failed ==="
    echo "Please check your MySQL credentials and try again."
fi
