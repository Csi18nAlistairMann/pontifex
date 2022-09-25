#!/usr/bin/php
<?php
//
// pontifect cryptosystem from Neal Stephenson's Cryptonomicron
// usage:
//  Manual edit of $decrypt_or_encrypt > 0 means decrypt, otherwise encrypt
//  echo 'plaintext' | ./pontifect.php <password>
//   or
//  echo 'ciphertext' | ./pontifect.php <password>
//
// This version based on the perl version in the book
// Alistair Mann, 2022

// Setup

$decrypt_or_encrypt = 1;
$encrypt_or_decrypt = $decrypt_or_encrypt ? -1 : 1;
// Password from argv[1], get it, uppercase all lowercase, then use A-Zs only
// for initial shuffle of above deck
$password = isset($argv[1]) ? $argv[1] : '';
$password = strtoupper($password);
$deck_of_cards = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
$deck_of_cards = initial_shuffle($deck_of_cards, $password);
$initial_shuffle_index = 0;

// Process and report

// Read everything from STDIN, uppercase all lowercase characters, and remove
// anything not A-Z
$fh = fopen( 'php://stdin', 'r' );
$output = '';
while($line = fgets($fh)) {
  $line = strtoupper($line);
  $line = preg_replace('/[^A-Z]/', '', $line);
  $output .= $line;
}
fclose($fh);
// If encrypting, pad the length to the nearest multiple of 5 with character X
if (!$decrypt_or_encrypt) $output = pad_to_five($output);
// Run the engine over the input, either encrypting or decrypting
list($deck_of_cards, $output) = transform_text($deck_of_cards, $output,
					       $encrypt_or_decrypt,
					       $initial_shuffle_index);
// If decrypting, remove final 'X's - they may be padding
$output = remove_padding($decrypt_or_encrypt, $output);
// Now group output in runs of five characters before printing it
$output = divide_into_groups($output);
print("$output\n");

//
// Engine and helpers
//

// Padding and grouping

// If required, add X to the right of the output until the length is a multiple
// of five
function pad_to_five($output) {
  while (strlen($output) % 5)
    $output .= 'X';
  return $output;
}

// Remove any instance of X at right of message. In this implementation, this
// does mean a cleartext cannot end with the letter X
function remove_padding($decrypt_or_encrypt, $output) {
  if ($decrypt_or_encrypt)
    $output = rtrim($output, 'X');
  return $output;
}

// Divide the output into five character runs, seperated with a space
function divide_into_groups($output) {
  $tmp = '';
  for($idx = 0; $idx < strlen($output); $idx += 5)
    $tmp .= substr($output, $idx, 5) . ' ';
  $output = trim($tmp);
  return $output;
}

// Deck handling

// Get the ordinal of whichever card is in the $indexth place in the deck,
// capped to a maximum of 53
function get_nth_card_from_deck_cap53($deck_of_cards, $index) {
  $v = ord(substr($deck_of_cards, $index)) - 32;
  $v = ($v > 53) ? 53: $v;
  return $v;
}

// Rotate deck left by $index, but leave the last character alone
function rotate_deck_by_n($deck_of_cards, $index) {
  $deck_of_cards = substr($deck_of_cards, $index, strlen($deck_of_cards) -
			  $index - 1) .
    substr($deck_of_cards, 0, $index) . substr($deck_of_cards, -1);
  return $deck_of_cards;
}

// If U (or V) is at the right of the deck, move it to the left. Either way,
// next move it one place right.
function rotate_uv_right($deck, $char) {
  if (substr($deck, -1) === $char)
    $deck = $char . substr($deck, 0, strlen($deck) - 1);
  $pos = strpos($deck, $char);
  if ($pos !== false)
    $deck = substr($deck, 0, $pos) . substr($deck, $pos + 1, 1) .
      $char . substr($deck, $pos + 2);
  return $deck;
}

// Core of system

// Use letters A-Z in the password to govern the shuffling of the deck from
// its initial state.
function initial_shuffle($deck_of_cards, $password) {
  while (strlen($password)) {
    $element = substr($password, 0, 1);
    $password = substr($password, 1);
    if ($element >= 'A' && $element <= 'Z') {
      $initial_shuffle_index = ord($element) - 64;
      list($deck_of_cards, $initial_shuffle_index) =
	run_the_engine($deck_of_cards, $initial_shuffle_index);
    }
  }
  return $deck_of_cards;
}

// Step through each character to be transformed: get a number from the engine,
// apply the magic, and replace it
function transform_text($deck_of_cards, $output, $encrypt_or_decrypt,
			$initial_shuffle_index) {
  for($idx = 0; $idx < strlen($output); $idx++) {
    $val = $encrypt_or_decrypt;
    list($deck_of_cards, $character) = run_the_engine($deck_of_cards,
						      $initial_shuffle_index);
    $val *= $character;
    $val += ord(substr($output, $idx, 1));
    $val -= 13;
    $val %= 26;
    $val += 65;
    $output[$idx] = chr($val);
  }
  return array($deck_of_cards, $output);
}

// Modify the deck before each use, then modify once more if this is the initial
// shuffle to tie the shuffle to the password. If we're not in the initial
// shuffle, obtain and return a calculated number.
function run_the_engine($deck_of_cards, $initial_shuffle_index) {
  $character = '';
  // Move U, and V twice; swap extreme ends and rotate deck left by whatever
  // ordinal is 53rd - last - place
  $deck_of_cards = rotate_uv_right($deck_of_cards, 'U');
  $deck_of_cards = rotate_uv_right($deck_of_cards, 'V');
  $deck_of_cards = rotate_uv_right($deck_of_cards, 'V');
  $deck_of_cards = preg_replace('/(.*)([UV].*[UV])(.*)/', '\3\2\1',
				$deck_of_cards);
  $tmp = get_nth_card_from_deck_cap53($deck_of_cards, 53);
  $deck_of_cards = rotate_deck_by_n($deck_of_cards, $tmp);

  if ($initial_shuffle_index) {
    // If initial shuffle, rotate deck left by whatever ordinal is in given
    // place
    $deck_of_cards = rotate_deck_by_n($deck_of_cards, $initial_shuffle_index);

  } else {
    // If not initial shuffle, return the character found indirectly through
    // the 0th, then the nth character. Recurse if it's too high
    $tmp = get_nth_card_from_deck_cap53($deck_of_cards, 0);
    $character = get_nth_card_from_deck_cap53($deck_of_cards, $tmp);
    if ($character > 52) {
      list($deck_of_cards, $character) = run_the_engine($deck_of_cards,
							$initial_shuffle_index);
    }
  }
  return array($deck_of_cards, $character);
}
?>
