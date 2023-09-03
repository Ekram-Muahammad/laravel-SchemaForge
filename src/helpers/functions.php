<?php

function escapeSpaces( $string ) {
    return  '/\b' . preg_quote( $string, '/' ) . '\b/i';
}
