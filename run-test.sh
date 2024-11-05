sudo docker compose -f compose.test.yaml up --build --wait

max_retry=5
counter=0
until curl --fail-with-body http://localhost:8080/test/sql.php
do
   sleep 3
   [[ counter -eq $max_retry ]] && echo "Failed!" && exit 1
   echo "Trying again. Try #$counter"
   ((counter++))
done

echo "############################# START TESTING ###########################"

# docker-compose exec webapp $TEST_COMMAND
#  --testsuite project
sudo docker exec --user root vnbiz-webapp-1 vendor/bin/phpunit --configuration phpunit.xml
TEST_RESULT=$?
echo "############################# END TESTING ###########################"

sudo docker compose  -f compose.test.yaml down


# Exit with the test result code
if [ $TEST_RESULT -eq 0 ]; then
  echo "Tests passed successfully."
else
  echo "Tests failed."
fi

echo $TEST_RESULT;
exit $TEST_RESULT