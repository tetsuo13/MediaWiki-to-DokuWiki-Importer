#! /bin/sh
# Mediawiki2Dokuwiki Converter
# originally by Johannes Buchner <buchner.johannes [at] gmx.at>
# changes by Frederik Tilkin:		- uses sed instead of perl
#				- resolved some bugs ('''''IMPORTANT!!!''''' becomes //**IMPORTANT!!!**//, // becomes <nowiki>//</nowiki> if it is not in a CODE block)
# 				- added functionality (multiple lines starting with a space become CODE blocks)
#
# Licence: GPL (http://www.gnu.org/licenses/gpl.txt)

# First escape things that are already DokuWiki but not MediaWiki syntax
# //	=>	<nowiki>//</nowiki> 	(only when it is NOT in a PREFORMATTED line, and when it is NOT in a LINK [] !)
# **	=>  <nowiki>**</nowiki		(only when it is NOT in a PREFORMATTED line, NOR on the beginning of a line)
# surround preformatted blocks (lines starting with space) with <PRE> so that it's correctly converted to DokuWiki <CODE> blocks later on

cat mediawiki \
	| sed -r -n '
		#starts with a SPACE, so it is part of a code block, just print and do nothing
		/^[ ]/ { p; d }
		#else: replace ALL **... strings (not at beginning of line)
		s/([^^][^\*]*)(\*\*+)/\1<nowiki>\2<\/nowiki>/g
		# 		also replace ALL //... strings 
		s/([^\/]*)(\/\/+)/\1<nowiki>\2<\/nowiki>/g
		#		change the ones that have been replaced in a link [] BACK to normal (do it twice in case [http://addres.com http://address.com] ) [quick and dirty]
		s/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/\1\3\5/g ; s/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/\1\3\5/g
		
		p
	  ' \
	| sed -r -n '
		# See also: http://www.grymoire.com/Unix/Sed.html#uh-40
		# 	http://en.wikipedia.org/wiki/Regular_expression
		# This is pretty advanced sed syntax, so I ll try to explain as much as possible
		################################################################################
		
		# if line starts with a space, add it to the hold buffer
		# we do this by 'branching' to :addtopre
		/^ [ ]*[^ ][^ ]*/ b addtopre
		# if line has only whitespace or is empty, the preformatted block is over, so we surround that with <pre>
		# we do this by 'branching' to :outputpre
		/^[ ]*$/ b outputpre
		# if line starts with NO whitespace, the preformatted block is over, so we surround that with <pre>
		/^[^ ].*$/ b outputpre
				
		#else this is a normal line
				#s/(.*)/NORMAL LINE: \1/g; p
			# print the line
			p
			#delete the current pattern space (so new cycle is started -> jumps to top)
			d
		
		# this is a line that should be part of a CODE block
		:addtopre
			#add it to the hold buffer
			H
				#s/(.*)/ADDED LINE: \1/g; p
			# if this is the last line of the file (end-of-file), empty this line and then output this last preformatted block
			$ { s/.*//g
				b outputpre
			}
			#delete the current pattern space (so new cycle is started -> jumps to top)
			d
		# this is where a paragraph is surrounded by <pre></pre>
		:outputpre
				#s/(.*)/END OF CODE LINE: \1/g; p
			# HOLD buffer is exchanged with the pattern space
			x

			# IF not empty, surround with <PRE> and PRINT the pattern space
			/(.+)/ {
				# surround it with <pre>
				s/(.+)/<pre>\1<\/pre>/g
				p
			}
			# exchange pattern space and hold buffer again, pattern is now the current line (not part of the preformatted block) and PRINT this line
			x
			p
			#delete the current pattern space			
			s/.*//g
			#and exchange this again with the hold buffer, so that the hold buffer is empty again			
			x
			#delete the current pattern space (so new cycle is started -> jumps to top)
			d
	' \
    > mediawiki0

# Headings
cat mediawiki0 \
   | sed -r 's/^[ ]*=([^=])/<h1> \1/g' \
   | sed -r 's/([^=])=[ ]*$/\1 <\/h1>/g' \
   | sed -r 's/^[ ]*==([^=])/<h2> \1/g' \
   | sed -r 's/([^=])==[ ]*$/\1 <\/h2>/g' \
   | sed -r 's/^[ ]*===([^=])/<h3> \1/g' \
   | sed -r 's/([^=])===[ ]*$/\1 <\/h3>/g' \
   | sed -r 's/^[ ]*====([^=])/<h4> \1/g' \
   | sed -r 's/([^=])====[ ]*$/\1 <\/h4>/g' \
   | sed -r 's/^[ ]*=====([^=])/<h5> \1/g' \
   | sed -r 's/([^=])=====[ ]*$/\1 <\/h5>/g' \
   | sed -r 's/^[ ]*======([^=])/<h6> \1/g' \
   | sed -r 's/([^=])======[ ]*$/\1 <\/h6>/g' \
   > mediawiki1
 
cat mediawiki1 \
   | sed -r 's/<\/?h1>/======/g' \
   | sed -r 's/<\/?h2>/=====/g' \
   | sed -r 's/<\/?h3>/====/g' \
   | sed -r 's/<\/?h4>/===/g' \
   | sed -r 's/<\/?h5>/==/g' \
   | sed -r 's/<\/?h6>/=/g'  \
   > mediawiki2
 
# lists
cat mediawiki2 \
  | sed -r 's/^[*#][*#][*#][*#]\*/          * /g'  \
  | sed -r 's/^[*#][*#][*#]\*/        * /g'    \
  | sed -r 's/^[*#][*#]\*/      * /g'      \
  | sed -r 's/^[*#]\*/    * /g'        \
  | sed -r 's/^\*/  * /g'                  \
  | sed -r 's/^[*#][*#][*#][*#]#/          - /g'  \
  | sed -r 's/^[*#][*#][*#]#/        - /g'    \
  | sed -r 's/^[*#][*#]#/      - /g'      \
  | sed -r 's/^[*#]#/    - /g'        \
  | sed -r 's/^#/  - /g'                   \
  > mediawiki3
 
 
#[url text] => [url|text]
cat mediawiki3 \
  | sed -r 's/([^[]|^)(\[[^] ]*) ([^]]*\])([^]]|$)/\1\2|\3\4/g' \
  > mediawiki4


#[link] => [[link]]
cat mediawiki4 \
  | sed -r 's/([^[]|^)(\[[^]]*\])([^]]|$)/\1[\2]\3/g' \
  > mediawiki5

# bold, italic
cat mediawiki5 \
  | sed -r "s/'''''(.*)'''''/\/\/**\1**\/\//g" \
  | sed -r "s/'''/**/g" \
  | sed -r "s/''/\/\//g" \
  > mediawiki6
 
# talks
cat mediawiki6 \
  | sed -r "s/^[ ]*:/>/g" \
  | sed -r "s/>:/>>/g" \
  | sed -r "s/>>:/>>>/g" \
  | sed -r "s/>>>:/>>>>/g" \
  | sed -r "s/>>>>:/>>>>>/g" \
  | sed -r "s/>>>>>:/>>>>>>/g" \
  | sed -r "s/>>>>>>:/>>>>>>>/g" \
  > mediawiki7

cat mediawiki7 \
   | sed -r "s/<code>/\'\'/g" \
   | sed -r "s/<\/code>/\'\'/g" \
  > mediawiki8

cat mediawiki8 \
   | sed -r "s/<pre>/<code>/g" \
   | sed -r "s/<\/pre>/<\/code>/g" \
  > mediawiki9

#100720-MSe: remove "<\code>\n \n<code>"
cat mediawiki9 \
   | sed 'N;N;s/<\/code>\n[ \t]*\n<code>//;P;D;D;' \
  > mediawiki10

# changes by Reiner Rottmann: - fixed erroneous interpretation of combined bold and italic text.
cat mediawiki10 \
   | sed -r "s/\*\*\/\//\/\/\*\*/g" \
  > mediawiki11

# Images / Files
cat mediawiki11 \
   | sed -r "s/\[\[([bB][iI][lL][dD]|[iI][mM][aA][gG][eE]|[dD][aA][tT][eE][iI]|[fF][iI][lL][eE]):([^\|\S]*)\|?\S*\]\]/{{mediawiki:\2}}/g" \
  > mediawiki12

cat mediawiki11 > dokuwiki
rm -f mediawiki mediawiki0 mediawiki1 mediawiki2 mediawiki3 mediawiki4 mediawiki5 mediawiki6 mediawiki7 mediawiki8 mediawiki9 mediawiki10 mediawiki11 mediawiki12
