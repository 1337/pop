<?php


try {
    throw new Exception("Bam!");
} catch (Exception $e) {
    throw new Exception("Wait");
} finally {
    echo "Hello";
}