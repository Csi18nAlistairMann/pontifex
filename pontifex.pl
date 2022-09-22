#!/usr/bin/perl -s
# $decrypt_or_encrypt = 1;
$encrypt_or_decrypt = $decrypt_or_encrypt ? -1 : 1;
$deck_of_cards = pack('C*', 33..86);
$password = shift;
$password =~ y/a-z/A-Z/;
$rotate_u_right = '$deck_of_cards =~ s/(.*)U$/U$1/; $deck_of_cards =~ s/U(.)/$1U/;';
($rotate_v_right = $rotate_u_right) =~ s/U/V/g;
$password =~ s/[A-Z]/$index_into_deck = ord($&) - 64, &run_the_engine/eg;
$index_into_deck = 0;
while(<>)
{
    y/a-z/A-Z/;
    y/A-Z//dc;
    $output .= $_;
}
$output .= 'X' while length($output) %5 && !$decrypt_or_encrypt;
$output =~ s/./chr(($encrypt_or_decrypt * &run_the_engine + ord($&) - 13) % 26 + 65)/eg;
$output =~ s/X*$// if $decrypt_or_encrypt;
$output =~ s/.{5}/$& /g;
print("$output\n");
sub get_nth_card_from_deck_cap53
{
    $v = ord(substr($deck_of_cards, $_[0])) - 32;
    $v > 53 ? 53 : $v;
}
sub rotate_deck_by_n
{
    $deck_of_cards =~ s/(.{$_[0]})(.*)(.)/$2$1$3/;
}
sub run_the_engine
{
    eval"$rotate_u_right$rotate_v_right$rotate_v_right";
    $deck_of_cards =~ s/(.*)([UV].*[UV])(.*)/$3$2$1/;
    &rotate_deck_by_n(&get_nth_card_from_deck_cap53(53));
    $index_into_deck ?
	(&rotate_deck_by_n($index_into_deck))
	: ($character = &get_nth_card_from_deck_cap53(&get_nth_card_from_deck_cap53(0)),
	   $character > 52 ? &run_the_engine : $character);
}
