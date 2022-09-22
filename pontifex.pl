#!/usr/bin/perl -s
#
# pontifect cryptosystem from Neal Stephenson's Cryptonomicron
# usage:
#  Manual edit of $decrypt_or_encrypt > 0 means decrypt, otherwise encrypt
#  echo 'plaintext' | ./pontifect.pl <password>
#   or
#  echo 'ciphertext' | ./pontifect.pl <password>
#
#
# Setup
# $decrypt_or_encrypt = 1;
$encrypt_or_decrypt = $decrypt_or_encrypt ? -1 : 1;
# Deck is Ascii string !"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUV
$deck_of_cards = pack('C*', 33..86);
# Set up code in strings for later
$rotate_u_right =
    '$deck_of_cards =~ s/(.*)U$/U$1/; $deck_of_cards =~ s/U(.)/$1U/;';
($rotate_v_right = $rotate_u_right) =~ s/U/V/g;
# Password from argv[1], get it, uppercase all lowercase, then use A-Zs only
# for initial shuffle of above deck
$password = shift;
$password =~ y/a-z/A-Z/;
$password =~ s/[A-Z]/$initial_shuffle_index = ord($&) - 64, &run_the_engine/eg;
$initial_shuffle_index = 0;
#
#
# Process and report
#
# Read everything from STDIN to the default var, uppercase all lowercase
# characters, and remove anything not A-Z
while(<>)
{
    y/a-z/A-Z/;
    y/A-Z//dc;
    $output .= $_;
}
# If encrypting, pad the length to the nearest multiple of 5 with character X
$output .= 'X' while length($output) %5 && !$decrypt_or_encrypt;
# Run the engine over the input, either encrypting or decrypting
$output =~ s/./chr(($encrypt_or_decrypt * &run_the_engine + ord($&) - 13) %
    26 + 65)/eg;
# If decrypting, remove final 'X's - they may be padding
$output =~ s/X*$// if $decrypt_or_encrypt;
# Now group output in runs of five characters before printing it
$output =~ s/.{5}/$& /g;
print("$output\n");
#
#
# Engine and helpers
#
sub get_nth_card_from_deck_cap53
{
    # Get the ordinal of whichever card is in the $_[0]th place in the deck
    $v = ord(substr($deck_of_cards, $_[0])) - 32;
    $v > 53 ? 53 : $v;
}
#
sub rotate_deck_by_n
{
    # Rotate deck left by $_[0], but leave the last character alone
    $deck_of_cards =~ s/(.{$_[0]})(.*)(.)/$2$1$3/;
}
#
sub run_the_engine
{
    # Move U, and V twice; swap extreme ends and rotate deck left by whatever
    # ordinal is 53rd - last - place
    eval"$rotate_u_right$rotate_v_right$rotate_v_right";
    $deck_of_cards =~ s/(.*)([UV].*[UV])(.*)/$3$2$1/;
    &rotate_deck_by_n(&get_nth_card_from_deck_cap53(53));
    #
    $initial_shuffle_index ?
	# If initial shuffle, rotate deck left by whatever ordinal is in given
	# place
	(&rotate_deck_by_n($initial_shuffle_index))
	:
	# If not initial shuffle, return the character found indirectly through
	# the 0th, then the nth character. Recurse if it's too high
	($character = &get_nth_card_from_deck_cap53(&get_nth_card_from_deck_cap53(0)),
	 $character > 52 ? &run_the_engine : $character);
}
