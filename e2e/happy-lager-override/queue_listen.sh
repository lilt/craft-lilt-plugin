#!/bin/sh

while true; do
    echo "Starting the queue listen process..."
    php craft queue/listen -v
    echo "Queue listen completed. Restarting in 30 seconds..."
    sleep 1
done
