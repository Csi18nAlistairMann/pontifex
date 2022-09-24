#!/usr/bin/php
<?php
   // ROSETTA: This script has the perl original commented out and the direct
   // PHP replacement underneath. You may prefer to look at pontifex.php which
   // skips the perl and makes the PHP more readable
   //
   /* #!/usr/bin/perl -s */
   // The hashbang line must come first, so here the original perl comes
   // second. For the remainder of this script the original perl comes first,

   //
   // pontifect cryptosystem from Neal Stephenson's Cryptonomicron
   // usage:
   //  Manual edit of $decrypt_or_encrypt > 0 means decrypt, otherwise encrypt
   //  echo 'plaintext' | ./pontifect-rosetta-perl.php <password>
   //   or
   //  echo 'ciphertext' | ./pontifect-rosetta-perl.php <password>
   //

   // Call tests used to establish identical behaviour. PHP only.
   /* test_rotate_uv_right(); */
   /* test_get_nth_card_from_deck_cap53(); */
   /* test_rotate_deck_by_n(); */
   /* test_initial_shuffle(); */
   /* test_pad_to_five(); */
   /* test_transform_text(); */
   /* test_remove_padding(); */
   /* test_divide_into_groups(); */
   /* test_run_the_engine(); */
   /* exit; */

   /* # Setup */

   /* # $decrypt_or_encrypt = 1; */
$decrypt_or_encrypt = 1;
/* $encrypt_or_decrypt = $decrypt_or_encrypt ? -1 : 1; */
$encrypt_or_decrypt = $decrypt_or_encrypt ? -1 : 1;
/* # Deck is Ascii string
   /* $deck_of_cards = pack('C*', 33..86); */
$deck_of_cards = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
/* # Set up code in strings for later */
/* $rotate_u_right = */
/*     '$deck_of_cards =~ s/(.*)U$/U$1/; $deck_of_cards =~ s/U(.)/$1U/;'; */
/* ($rotate_v_right = $rotate_u_right) =~ s/U/V/g; */
function rotate_uv_right($deck, $char) {
  if (substr($deck, -1) === $char)
    $deck = $char . substr($deck, 0, strlen($deck) - 1);
  $pos = strpos($deck, $char);
  if ($pos !== false)
    $deck = substr($deck, 0, $pos) . substr($deck, $pos + 1, 1) .
      $char . substr($deck, $pos + 2);
  return $deck;
}
/* # Password from argv[1], get it, uppercase all lowercase, then use A-Zs only */
/* # for initial shuffle of above deck */
/* $password = shift; */
$password = isset($argv[1]) ? $argv[1] : '';
/* $password =~ y/a-z/A-Z/; */
$password = strtoupper($password);
/* $password =~ s/[A-Z]/$initial_shuffle_index = ord($&) - 64, &run_the_engine/eg; */
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
$deck_of_cards = initial_shuffle($deck_of_cards, $password);
/* $initial_shuffle_index = 0; */
$initial_shuffle_index = 0;

/* # */
/* # */
/* # Process and report */
/* # */
/* # Read everything from STDIN to the default var, uppercase all lowercase */
/* # characters, and remove anything not A-Z */
/* while(<>) */
/* { */
/*     y/a-z/A-Z/; */
/*     y/A-Z//dc; */
/*     $output .= $_; */
/* } */
$fh = fopen( 'php://stdin', 'r' );
$output = '';
while($line = fgets($fh)) {
  $line = strtoupper($line);
  $line = preg_replace('/[^A-Z]/', '', $line);
  $output .= $line;
}
fclose($fh);
/* # If encrypting, pad the length to the nearest multiple of 5 with character X */
/* $output .= 'X' while length($output) %5 && !$decrypt_or_encrypt; */
function pad_to_five($output) {
  while (strlen($output) % 5)
    $output .= 'X';
  return $output;
}
if (!$decrypt_or_encrypt)
  $output = pad_to_five($output);
/* # Run the engine over the input, either encrypting or decrypting */
/* $output =~ s/./chr(($encrypt_or_decrypt * &run_the_engine + ord($&) - 13) % */
/*     26 + 65)/eg; */
function transform_text($deck_of_cards, $output, $encrypt_or_decrypt, $initial_shuffle_index) {
  for($idx = 0; $idx < strlen($output); $idx++) {
    $val = $encrypt_or_decrypt;
    list($deck_of_cards, $character) = run_the_engine($deck_of_cards, $initial_shuffle_index);
    $val *= $character;
    $val += ord(substr($output, $idx, 1));
    $val -= 13;
    $val %= 26;
    $val += 65;
    $output[$idx] = chr($val);
  }
  return array($deck_of_cards, $output);
}
list($deck_of_cards, $output) = transform_text($deck_of_cards, $output, $encrypt_or_decrypt, $initial_shuffle_index);

/* # If decrypting, remove final 'X's - they may be padding */
/* $output =~ s/X*$// if $decrypt_or_encrypt; */
function remove_padding($decrypt_or_encrypt, $output) {
  if ($decrypt_or_encrypt)
    $output = rtrim($output, 'X');
  return $output;
}
$output = remove_padding($decrypt_or_encrypt, $output);
/* # Now group output in runs of five characters before printing it */
/* $output =~ s/.{5}/$& /g; */
function divide_into_groups($output) {
  $tmp = '';
  for($idx = 0; $idx < strlen($output); $idx += 5) {
    $tmp .= substr($output, $idx, 5) . ' ';
  }
  $output = trim($tmp);
  return $output;
}
$output = divide_into_groups($output);
/* print("$output\n"); */
print("$output\n");
/* # */
/* # */
/* # Engine and helpers */
/* # */
/* sub get_nth_card_from_deck_cap53 */
/* { */
/*     # Get the ordinal of whichever card is in the $_[0]th place in the deck */
/*     $v = ord(substr($deck_of_cards, $_[0])) - 32; */
/*     $v > 53 ? 53 : $v; */
/* } */
function get_nth_card_from_deck_cap53($deck_of_cards, $index) {
  $v = ord(substr($deck_of_cards, $index)) - 32;
  $v = ($v > 53) ? 53: $v;
  return $v;
}
/* # */
/* sub rotate_deck_by_n */
/* { */
/*     # Rotate deck left by $_[0], but leave the last character alone */
/*     $deck_of_cards =~ s/(.{$_[0]})(.*)(.)/$2$1$3/; */
/* } */
function rotate_deck_by_n($deck_of_cards, $index) {
  $deck_of_cards = substr($deck_of_cards, $index, strlen($deck_of_cards) - $index - 1) .
    substr($deck_of_cards, 0, $index) . substr($deck_of_cards, -1);
  return $deck_of_cards;
}
/* # */
/* sub run_the_engine */
/* { */
/*     # Move U, and V twice; swap extreme ends and rotate deck left by whatever */
/*     # ordinal is 53rd - last - place */
/*     eval"$rotate_u_right$rotate_v_right$rotate_v_right"; */
/*     $deck_of_cards =~ s/(.*)([UV].*[UV])(.*)/$3$2$1/; */
/*     &rotate_deck_by_n(&get_nth_card_from_deck_cap53(53)); */
/*     # */
/*     $initial_shuffle_index ? */
/*	# If initial shuffle, rotate deck left by whatever ordinal is in given */
/*	# place */
/*	(&rotate_deck_by_n($initial_shuffle_index)) */
/*	: */
/*	# If not initial shuffle, return the character found indirectly through */
/*	# the 0th, then the nth character. Recurse if it's too high */
/*	($character = &get_nth_card_from_deck_cap53(&get_nth_card_from_deck_cap53(0)), */
/*	 $character > 52 ? &run_the_engine : $character); */
/* } */
function run_the_engine($deck_of_cards, $initial_shuffle_index) {
  $character = '';
  $deck_of_cards = rotate_uv_right($deck_of_cards, 'U');
  $deck_of_cards = rotate_uv_right($deck_of_cards, 'V');
  $deck_of_cards = rotate_uv_right($deck_of_cards, 'V');
  $deck_of_cards = preg_replace('/(.*)([UV].*[UV])(.*)/', '\3\2\1', $deck_of_cards);
  $deck_of_cards = rotate_deck_by_n($deck_of_cards, get_nth_card_from_deck_cap53($deck_of_cards, 53));

  if ($initial_shuffle_index) {
    $deck_of_cards = rotate_deck_by_n($deck_of_cards, $initial_shuffle_index);
  } else {
    $character = get_nth_card_from_deck_cap53($deck_of_cards, get_nth_card_from_deck_cap53($deck_of_cards, 0));
    if ($character > 52) {
      list($deck_of_cards, $character) = run_the_engine($deck_of_cards, $initial_shuffle_index);
    }
  }
  return array($deck_of_cards, $character);
}

// tests

function test_rotate_uv_right() {
  // deliberate error
  $deck = "ABUCD";
  $char = 'Z';
  $deck = rotate_uv_right($deck, $char);
  if ($deck != "ABUCD") die();
  // U in center
  $deck = "ABUCD";
  $char = 'U';
  $deck = rotate_uv_right($deck, $char);
  if ($deck != "ABCUD") die();
  // U at left
  $deck = "UABCD";
  $char = 'U';
  $deck = rotate_uv_right($deck, $char);
  if ($deck != "AUBCD") die();
  // U at right
  $deck = "ABCDU";
  $char = 'U';
  $deck = rotate_uv_right($deck, $char);
  if ($deck != "AUBCD") die();
  print(__FUNCTION__ . " all tests pass\n");
}

function test_get_nth_card_from_deck_cap53() {
  // index [0]
  $deck = "ABCDE";
  $index = 0;
  $v = get_nth_card_from_deck_cap53($deck, $index);
  if ($v != 33) die ();
  // index [4]
  $deck = "ABCDE";
  $index = 4;
  $v = get_nth_card_from_deck_cap53($deck, $index);
  if ($v != 37) die ();
  // index Z (Not 58, capped at 53)
  $deck = "ZBCDE";
  $index = 0;
  $v = get_nth_card_from_deck_cap53($deck, $index);
  if ($v != 53) die ();

  print(__FUNCTION__ . " all tests pass\n");
}

function test_rotate_deck_by_n() {
  // mid deck
  $deck = "ABCDE";
  $index = 3;
  $deck = rotate_deck_by_n($deck, $index);
  if ($deck != "DABCE") die();
  // start of deck
  $deck = "ABCDE";
  $index = 0;
  $deck = rotate_deck_by_n($deck, $index);
  if ($deck != "ABCDE") die();
  // second
  $deck = "ABCDE";
  $index = 1;
  $deck = rotate_deck_by_n($deck, $index);
  if ($deck != "BCDAE") die();

  print(__FUNCTION__ . " all tests pass\n");
}

function test_initial_shuffle() {
  // no password - no initial shuffle
  $deck = "ABCDE";
  $password = "";
  $deck = initial_shuffle($deck, $password);
  if ($deck !== "ABCDE") die();
  // password ABC
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $password = "ABC";
  $deck = initial_shuffle($deck, $password);
  if ($deck !== "*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRS!TU#\$V%&'()\"") die();
  // password long length
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $password = "THE QUICK BROWN FOX JUMPED OVER THE LAZY DOG";
  $deck = initial_shuffle($deck, $password);
  if ($deck !== "GMQ7/?V1R#9*B)'!AT,-80<U\$:;\"K6>4O%2+LN(S.&FJHEC5@P3ID=") die($deck);

  print(__FUNCTION__ . " all tests pass\n");
}

function test_pad_to_five() {
  // test already five
  $o = "ABCDE";
  $p = pad_to_five($o);
  if ($o != $p) die();

  // test one
  $o = "A";
  $o = pad_to_five($o);
  if ($o != "AXXXX") die();

  // test four
  $o = "ABCD";
  $o = pad_to_five($o);
  if ($o != "ABCDX") die();

  print(__FUNCTION__ . " all tests pass\n");
}

function test_transform_text() {
  // Encrypt HELLOWORLD to get LBVJWVGXPK
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $output = "HELLOWORLD";
  $encrypt_or_decrypt = 1;
  $initial_shuffle_index = 0;
  list($deck, $output) = transform_text($deck, $output,
					$encrypt_or_decrypt,
					$initial_shuffle_index);
  if ($output != "LBVJWVGXPK") die();
  if ($deck != "-S/06789V=>?@ABCDEFGHIJKLMNOPQRT!\")*+#\$12345.&:;<%U(,'") die();
  // Decrypt  to get HELLOWORLD
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $output = "LBVJWVGXPK";
  $encrypt_or_decrypt = -1;
  $initial_shuffle_index = 0;
  list($deck, $output) = transform_text($deck, $output,
					$encrypt_or_decrypt,
					$initial_shuffle_index);
  if ($output != "HELLOWORLD") die();
  if ($deck != "-S/06789V=>?@ABCDEFGHIJKLMNOPQRT!\")*+#\$12345.&:;<%U(,'") die();

  print(__FUNCTION__ . " all tests pass\n");
}

function test_remove_padding() {
  // test ignored bc of dir
  $dir = 0;
  $output1 = 'AXXX';
  $output2 = remove_padding($dir, $output1);
  if ($output2 != $output1) die();

  // test ignored bc no padding
  $dir = 1;
  $output1 = 'ABCDE';
  $output2 = remove_padding($dir, $output1);
  if ($output2 != $output1) die();

  // test remove 1 pad
  $dir = 1;
  $output1 = 'ABCDEX';
  $output2 = remove_padding($dir, $output1);
  if ($output2 != "ABCDE") die();

  print(__FUNCTION__ . " all tests pass\n");
}

function test_divide_into_groups() {
  //test short
  $output1 = "ABC";
  $output2 = divide_into_groups($output1);
  if ($output2 != $output1) die();

  //test empty
  $output1 = "";
  $output2 = divide_into_groups($output1);
  if ($output2 != $output1) die();

  //test five
  $output1 = "ABCDE";
  $output2 = divide_into_groups($output1);
  if ($output2 != $output1) die();

  //test six
  $output1 = "ABCDEF";
  $output2 = divide_into_groups($output1);
  if ($output2 != "ABCDE F") die();

  //test ten
  $output1 = "ABCDEFGHIJ";
  $output2 = divide_into_groups($output1);
  if ($output2 != "ABCDE FGHIJ") die();

  //test eleven
  $output1 = "ABCDEFGHIJK";
  $output2 = divide_into_groups($output1);
  if ($output2 != "ABCDE FGHIJ K") die();

  print(__FUNCTION__ . " all tests pass\n");
}

function test_run_the_engine() {
  // 1 initial shuffle
  $deck = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $initial_shuffle_index = 1;
  list($deck, $char) = run_the_engine($deck, $initial_shuffle_index);
  if ($deck != "#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV\"!") die($deck);
  // 1000 initial shuffles
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $initial_shuffle_index = 1;
  for($a = 0; $a < 1000; $a++) {
    list($deck, $char) = run_the_engine($deck, $initial_shuffle_index);
  }
  if ($deck != "TPI(3-UAR>2E0!7/)=@Q*&D?%'.CGF9,64B8H1O5J<+K#\$SM\":;LVN") die($deck);
  // 2^17 initial shuffles
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $initial_shuffle_index = 1;
  for($a = 0; $a < 131072; $a++) {
    list($deck, $char) = run_the_engine($deck, $initial_shuffle_index);
  }
  if ($deck != ";DI5,4@UO60&/(JT!-:F\$Q#H\"8MNL.=?P>EV+9%AGR1K*2)BC7<3'S") die($deck);
  // 0 initial shuffles and 1 call
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $initial_shuffle_index = 0;
  list($deck, $char) = run_the_engine($deck, $initial_shuffle_index);
  if ($deck != "\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV!") die($deck);
  if ($char != 4) die($char);
  // 0 initial shuffles and 100 calls (includes 4 recursing)
  $deck = "!\"#\$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV";
  $initial_shuffle_index = 0;
  for ($a = 0; $a < 100; $a++) {
    list($deck, $char) = run_the_engine($deck, $initial_shuffle_index);
  }
  if ($deck != "%'#KNHSJ,Q/)P>9GET2;(&+15.A63@B0D7MV?LRU-:\$CO\"F4<=8*!I") die($deck);
  if ($char != 40) die($char);

  print(__FUNCTION__ . " all tests pass\n");
}
?>
