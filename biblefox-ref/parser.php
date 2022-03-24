<?php

/**
 * Class for specifying parsing options and storing parsing results
 *
 */
class BfoxRefParser
{
    /**
     * If not null, stores all bible references together in one BfoxRef
     * @var BfoxRef
     */
    public $total_ref;

    /**
     * If not null, stores an array of all the individual BfoxRef found
     * @var array of BfoxRef
     */
    public $ref_array;

    /**
     * If not null, stores a string containing all the leftover characters not considered part of a Bible reference
     * @var string
     */
    public $leftovers;

    /**
     * The max level of book abbreviations to allow
     * @var integer
     */
    public $max_level = 0;

    /**
     * Whether to allow references to whole books
     * @var boolean
     */
    public $add_whole_books = true;

    /**
     * Whether to require a space before the chapter/verse section
     *
     * ie. if true, John3:16 is not accepted
     *
     * @var boolean
     */
    public $require_space_before_cv = false;

    /**
     * Whether to parse the string forward or reverse
     *
     * Parsing forward fails on strings like 'Genesis 3; 1 Samuel 5' because it thinks that the 1 is a chapter number for Genesis (result: Genesis 1,3)
     * Parsing in reverse fixes this, because the parser finds 1 Samuel before Genesis, because it gives priority to bible references appearing later.
     *
     * @var boolean
     */
    public $forward = false;

    /**
     * Returns a BfoxRef for all the bible references in a string
     *
     * @param string $str
     * @return BfoxRef
     */
    public static function simple($str, &$leftovers = null)
    {
        $parser = new BfoxRefParser;
        $parser->total_ref = new BfoxRef; // Save total_ref
        $parser->leftovers = $leftovers; // Save leftovers if not null
        $parser->max_level = 2; // Include all book abbreviations

        $leftovers = $parser->parse_string($str);

        return $parser->total_ref;
    }

    /**
     * Returns the total bible references if and only if there were no leftovers
     *
     * @param $str
     * @return BfoxRef or false
     */
    public static function no_leftovers($str)
    {
        $parser = new BfoxRefParser;
        $parser->total_ref = new BfoxRef; // Save total_ref
        $parser->leftovers = true; // Save the leftovers
        $parser->max_level = 2; // Include all book abbreviations

        $leftovers = $parser->parse_string($str);

        if (empty($leftovers)) {
            return $parser->total_ref;
        } else {
            return false;
        }
    }

    /**
     * Returns a BfoxRef for the string, with some support for BibleGroupPassage
     *
     * @param $str
     * @return BfoxRef
     */
    public static function with_groups($str)
    {
        if (isset(BibleMeta::$book_groups[$str])) {
            return new BibleGroupPassage($str);
        } else {
            return self::simple($str);
        }
    }

    /**
     * Parses HTML content for Bible references. Returns content modified by the optional $replace_func callback
     *
     * Optionally stores the total BfoxRef in $total_ref parameter
     * Optionally modifies the content using the $replace_func parameter
     *
     * @param string $html
     * @param BfoxRef $total_ref
     * @param function $replace_func
     * @return string HTML content modified by the optional $replace_func callback
     */
    public static function simple_html($html, BfoxRef $total_ref = null, $replace_func = null)
    {
        $parser = new BfoxRefParser;
        $parser->total_ref = $total_ref; // Save total_ref
        $parser->replace_func = $replace_func; // Modify string with the replace_func callback
        $parser->max_level = 1; // Include 2 letter book abbreviations, but not the risky ones (like so, is, ex, am)
        $parser->add_whole_books = false; // Don't allow whole book references

        return $parser->parse_string_html($html);
    }


    private static $book_id_lookup = array(
        'genesis' => 1, 'gen' => 1, 'ge' => 1, 'gn' => 1,
        'exod' => 2, 'exo' => 2, 'exodus' => 2, 'ex' => 2,
        'leviticus' => 3, 'lev' => 3, 'le' => 3, 'lv' => 3,
        'num' => 4, 'numbers' => 4, 'nb' => 4, 'nm' => 4, 'nu' => 4, 'numeros' => 4, 'números' => 4,
        'deuteronomy' => 5, 'deut' => 5, 'deu' => 5, 'dt' => 5,
        'jsh' => 6, 'jos' => 6, 'josh' => 6, 'joshua' => 6, 'josue' => 6, 'josué' => 6,
        'judges' => 7, 'judg' => 7, 'jdg' => 7, 'jdgs' => 7, 'jg' => 7, 'jueces' => 7, 'juez' => 7,
        'rut' => 8, 'rth' => 8, 'ruth' => 8, 'ru' => 8,
        '1samuel' => 9, '1sam' => 9, '1sm' => 9, '1sa' => 9, '1s' => 9,
        '2samuel' => 10, '2sam' => 10, '2sm' => 10, '2sa' => 10, '2s' => 10,
        '1kings' => 11, '1king' => 11, '1kgs' => 11, '1kin' => 11, '1ki' => 11, '1k' => 11, '1reyes' => 11,
        '2kings' => 12, '2king' => 12, '2kgs' => 12, '2kin' => 12, '2ki' => 12, '2k' => 12, '2reyes' => 11,
        '1chronicles' => 13, '1chron' => 13, '1chr' => 13, '1ch' => 13, '1crónicas' => 13, '1cronicas' => 13, '1cron' => 13,
        '2chronicles' => 14, '2chron' => 14, '2chr' => 14, '2ch' => 14, '2crónicas' => 13, '2cronicas' => 13, '2cron' => 13,
        'ezra' => 15, 'ezr' => 15, 'esdras' => 15, 'esd' => 15,
        'neh' => 16, 'nehemiah' => 16, 'ne' => 16, 'nehemías' => 16, 'nehemias' => 16,
        'esther' => 17, 'esth' => 17, 'est' => 17, 'es' => 17, 'ester' => 17,
        'job' => 18, 'jb' => 18,
        'pss' => 19, 'psm' => 19, 'psa' => 19, 'psalms' => 19, 'pslm' => 19, 'psalm' => 19, 'ps' => 19, 'salmos' => 19, 'slm' => 19, 'salm' => 19,
        'pro' => 20, 'prv' => 20, 'prov' => 20, 'proverbs' => 20, 'pr' => 20, 'proverbios' => 20,
        'eccl' => 21, 'ecc' => 21, 'qoheleth' => 21, 'qoh' => 21, 'eccles' => 21, 'ecclesiastes' => 21, 'ec' => 21, 'eclesiastés' => 21, 'eclesiastes' => 21,'ecles' => 21, 'ecl' => 21,
        'sng' => 22, 'sos' => 22, 'song of songs' => 22, 'canticles' => 22, 'canticle of canticles' => 22, 'song' => 22, 'song of solomon' => 22, 'so' => 22, 'cantar de los cantares' => 22, 'cantares' => 22, 'cantar' => 22,
        'isaiah' => 23, 'isa' => 23, 'jer' => 24, 'jeremiah' => 24, 'jr' => 24, 'je' => 24, 'isaías' => 24, 'isaias' => 24,
        'lamentations' => 25, 'lam' => 25, 'la' => 25, 'lamentaciones' => 25, 'lament' => 25, 'lm' => 25,
        'ezk' => 26, 'eze' => 26, 'ezek' => 26, 'ezekiel' => 26, 'ezequiel' => 26, 'ezq' => 26, 'ezeq' => 26,
        'dan' => 27, 'daniel' => 27, 'dn' => 27, 'da' => 27,
        'hosea' => 28, 'hos' => 28, 'ho' => 28, 'oseas' => 28, 'os' => 28,
        'jol' => 29, 'joe' => 29, 'joel' => 29, 'jl' => 29,
        'amos' => 30, 'amo' => 30, 'am' => 30, 'amós' => 30,
        'oba' => 31, 'obad' => 31, 'obadiah' => 31, 'ob' => 31, 'abdías' => 31, 'abdias' => 31, 'abd' => 31,
        'jonah' => 32, 'jnh' => 32, 'jon' => 32, 'jonas' => 32, 'jonás' => 32,
        'micah' => 33, 'mic' => 33, 'miqueas' => 33, 'miq' => 33,
        'nam' => 34, 'nah' => 34, 'nahum' => 34, 'na' => 34,
        'habakkuk' => 35, 'hab' => 35, 'habacuc' => 35,
        'zephaniah' => 36, 'zeph' => 36, 'zep' => 36, 'zp' => 36, 'sofonías' => 36, 'sofonias' => 36, 'sof' => 36,
        'hag' => 37, 'haggai' => 37, 'hg' => 37, 'hageo' => 37,
        'zechariah' => 38, 'zech' => 38, 'zec' => 38, 'zc' => 38, 'zacarías' => 38, 'zacarias' => 38, 'zac' => 38,
        'mal' => 39, 'malachi' => 39, 'ml' => 39, 'malaquías' => 39, 'malaquias' => 39, 'malaq' => 39,
        'matthew' => 40, 'matt' => 40, 'mat' => 40, 'mt' => 40, 'mateo' => 40,
        'mrk' => 41, 'mark' => 41, 'mr' => 41, 'mk' => 41, 'marcos' => 41, 'mrc' => 41,
        'luke' => 42, 'luk' => 42, 'lk' => 42, 'lucas' => 42, 'luc' => 42,
        'jhn' => 43, 'john' => 43, 'jn' => 43, 'juan' => 43, 'ju' => 43,
        'acts' => 44, 'act' => 44, 'ac' => 44, 'hechos' => 44, 'hech' => 44,
        'rom' => 45, 'romans' => 45, 'ro' => 45, 'rm' => 45, 'romanos' => 45,
        '1corinthians' => 46, '1cor' => 46, '1co' => 46, '1corintios' => 46,
        '2corinthians' => 47, '2cor' => 47, '2co' => 47, '2corintios' => 47,
        'gal' => 48, 'galatians' => 48, 'ga' => 48, 'gálatas' => 48, 'galatas' => 48,
        'ephesians' => 49, 'ephes' => 49, 'eph' => 49, 'efesios' => 49, 'efes' => 49,
        'philippians' => 50, 'phil' => 50, 'php' => 50, 'filipenses' => 50,
        'col' => 51, 'colossians' => 51, 'colosenses' => 51, 'colos' => 51,
        '1thessalonians' => 52, '1thess' => 52, '1thes' => 52, '1th' => 52, '1tesalonicenses' => 52, '1tes' => 52,
        '2thessalonians' => 53, '2thess' => 53, '2thes' => 53, '2th' => 53, '2tesalonicenses' => 53, '2tes' => 53,
        '1timothy' => 54, '1tim' => 54, '1ti' => 54, '1timoteo' => 54,
        '2timothy' => 55, '2tim' => 55, '2ti' => 55,  '2timoteo' => 55,
        'tit' => 56, 'titus' => 56, 'tito' => 56,
        'philemon' => 57, 'philem' => 57, 'phm' => 57, 'filemon' => 57, 'filemón' => 57,
        'hebrews' => 58, 'heb' => 58, 'hebreos' => 59,
        'jas' => 59, 'james' => 59, 'jm' => 59,
        '1peter' => 60, '1pet' => 60, '1pt' => 60, '1pe' => 60, '1pedro' => 60,
        '2peter' => 61, '2pet' => 61, '2pt' => 61, '2pe' => 61, '2pedro' => 61,
        '1john' => 62, '1jhn' => 62, '1joh' => 62, '1jn' => 62, '1jo' => 62, '1juan' => 62,
        '2john' => 63, '2jhn' => 63, '2joh' => 63, '2jn' => 63, '2jo' => 63, '2juan' => 63,
        '3john' => 64, '3jhn' => 64, '3joh' => 64, '3jn' => 64, '3jo' => 64, '3juan' => 64,
        'jud' => 65, 'jude' => 65, 'judas' => 65,
        'revelation' => 66, 'revelations' => 66, 'rev' => 66, 're' => 66, 'revelaciones' => 66, 'apocalipsis' => 66, 'apoc' => 66
    );

    // Note: Books have to be reverse sorted to give priority to the longer book names
    private static $book_regexes = array(0 => array(
        // Forward
        0 => array(
			0 => 'zephaniah|sofonías|sofonias|sof|zeph|zep|zechariah|zc|zacarias|zacarías|zac|zech|zec|wisdom\s+of\s+solomon|wisdom|wisd\s+of\s+sol|wis|tobit|tob|titus|tit|susanna|sus|sos|song\s+thr|song\s+ot\s+the\s+three\s+children|song\s+of\s+the\s+three\s+youths|song\s+of\s+the\s+three\s+jews|song\s+of\s+the\s+three\s+holy\s+children|song\s+of\s+the\s+three|song\s+of\s+songs|song\s+of\s+solomon|song|sng|sirach|sir|ruth|rut|rth|romans|romanos|rom|revelations|revelation|rev|rest\s+of\s+esther|rest\s+esther|qoheleth|qoh|pss|psm|pslm|psalms|psalm|psa|salmos|slm|salm|prv|proverbs|prov|pro|proverbios|prayer\s+of\s+manasses|prayer\s+of\s+manasseh|prayer\s+of\s+azariah|pr\s+man|pr\s+az|pma|php|phm|philippians|philemon|philem|phil|obadiah|abdías|abdias|abd|obad|oba|numbers|numeros|números|num|nehemiah|neh|nehemías|nehemias|nam|nahum|nah|mrk|micah|mic|miqueas|miq|matthew|matt|mat|mateo|mark|marcos|mrc|malachi|mal|malaquías|malaquias|malaq|luke|luk|lucas|luc|ltr\s+jer|lje|leviticus|lev|letter\s+of\s+jeremiah|let\s+of\s+jer|lamentations|lamentaciones|lament|lm|lam|judith|judges|judg|jude|jud|jueces|juez|jth|jsh|joshua|josh|jos|josue|josué|jonas|jonás|jonah|jon|jol|john|joel|joe|job|jnh|jhn|jeremiah|jer|jdth|jdt|jdgs|jdg|jas|james|isaiah|isa|hosea|oseas|os|hos|hebrews|heb|haggai|hageo|hag|habakkuk|habacuc|hab|genesis|gen|galatians|gal|ezra|esdras|esd|ezr|ezk|ezequiel|ezq|ezeq|ezekiel|ezek|eze|exodus|exod|exo|esther|esth|est|esg|ephesians|ephes|eph|ecclus|ecclesiasticus|ecclesiastes|eccles|eccl|ecc|eclesiastés|eclesiastes|ecles|ecl|deuteronomy|deut|deu|daniel|dan|colossians|col|canticles|canticle\s+of\s+canticles|cantar\s+de\s+los\s+cantares|bel\s+dragon|bel\s+and\s+the\s+dragon|bel|baruch|bar|azariah|amos|amós|amo|aes|additions\s+to\s+esther|addesth|add\s+to\s+esth|add\s+to\s+es|acts|act|3john|3joh|3jo|3jn|3jhn|2timothy|2tim|2ti|2thessalonians|2thess|2thes|2th|2sm|2samuel|2sam|2sa|2pt|2peter|2pet|2pe|2maccabees|2macc|2mac|2ma|2kings|2king|2kin|2ki|2kgs|2john|2joh|2jo|2jn|2jhn|2esdras|2esdr|2esd|2es|2corinthians|2cor|2co|2chronicles|2chron|2chr|2ch|1timothy|1tim|1ti|1thessalonians|1thess|1thes|1th|1sm|1samuel|1sam|1sa|1pt|1peter|1pet|1pe|1maccabees|1macc|1mac|1ma|1reyes|2reyes|1kings|1king|1kin|1ki|1kgs|1john|1joh|1jo|1jn|1jhn|1esdras|1esdr|1esd|1es|1corinthians|1cor|1co|1chronicles|1chron|1chr|1ch|1crónicas|1cronicas|1cron|2crónicas|2cronicas|2cron',
			1 => 'timothy|tim|ti|thessalonians|thess|thes|th|sm|samuel|sam|sa|pt|peter|pet|pe|maccabees|macc|mac|ma|kings|king|kin|ki|kgs|john|joh|jo|jn|jhn|esdras|esdr|esd|es|corinthians|cor|co|chronicles|chron|chr|ch',
			2 => 'timothy|tim|ti|thessalonians|thess|thes|th|sm|samuel|sam|sa|pt|peter|pet|pe|maccabees|macc|mac|ma|kings|king|kin|ki|kgs|john|joh|jo|jn|jhn|esdras|esdr|esd|es|corinthians|cor|co|chronicles|chron|chr|ch',
			3 => 'john|joh|jo|jn|jhn'),
        1 => array(
			0 => 'zp|zc|ws|tb|ru|ro|rm|re|ps|pr|ob|nu|nm|ne|nb|na|mt|mr|ml|mk|lv|lk|le|la|jr|jn|jm|jl|jg|je|jb|ho|hg|gn|ge|ga|es|ec|dt|dn|da|ac|2s|2m|2k|1s|1m|1k',
			1 => 's|m|k',
			2 => 's|m|k'
        ),
        2 => array(
			0 => 'so|is|ex|am'
        )
    ), 1 => array(
        // Reverse
        0 => array(
			0 => 'hainahpez|saínofos|sainofos|fos|hpez|pez|hairahcez|hcez|cez|sairacaz|saíracaz|caz|nomolos\s+fo\s+modsiw|modsiw|los\s+fo\s+dsiw|siw|tibot|bot|sutit|tit|annasus|sus|sos|rht\s+gnos|nerdlihc\s+eerht\s+eht\s+to\s+gnos|shtuoy\s+eerht\s+eht\s+fo\s+gnos|swej\s+eerht\s+eht\s+fo\s+gnos|nerdlihc\s+yloh\s+eerht\s+eht\s+fo\s+gnos|eerht\s+eht\s+fo\s+gnos|sgnos\s+fo\s+gnos|nomolos\s+fo\s+gnos|gnos|gns|hcaris|ris|htur|tur|htr|snamor|sonamor|mor|snoitalever|noitalever|ver|rehtse\s+fo\s+tser|rehtse\s+tser|htelehoq|hoq|ssp|msp|mlsp|smlasp|mlasp|somlas|mls|mlas|asp|vrp|sbrevorp|vorp|orp|soibrevorp|sessanam\s+fo\s+reyarp|hessanam\s+fo\s+reyarp|hairaza\s+fo\s+reyarp|nam\s+rp|za\s+rp|amp|php|mhp|snaippilihp|nomelihp|melihp|lihp|haidabo|saidba|saídba|dba|dabo|abo|srebmun|soremun|soremún|mun|haimehen|hen|saimehen|saímehen|man|muhan|han|krm|hacim|saeuqim|qim|cim|wehttam|ttam|oetam|tam|kram|socram|crm|ihcalam|saiuqalam|saíuqalam|qalam|lam|ekul|kul|sacul|cul|rej\s+rtl|ejl|sucitivel|vel|haimerej\s+fo\s+rettel|rej\s+fo\s+tel|snoitatnemal|senoicatnemal|tnemal|ml|mal|htiduj|segduj|gduj|eduj|seceuj|zeuj|duj|htj|hsj|auhsoj|hsoj|soj|eusoj|éusoj|hanoj|noj|sánoj|sanoj|loj|nhoj|leoj|eoj|boj|hnj|nhj|haimerej|rej|htdj|tdj|sgdj|gdj|saj|semaj|haiasi|asi|aesoh|soh|swerbeh|beh|iaggah|oegah|gah|kukkabah|cucabah|bah|siseneg|neg|snaitalag|lag|arze|rze|sardse|dse|kze|leikeze|keze|eze|leiuqeze|qze|qeze|sudoxe|doxe|oxe|rehtse|htse|tse|gse|snaisehpe|sehpe|hpe|sulcce|sucitsaiselcce|setsaiselcce|selcce|lcce|cce|sétsaiselce|setsaiselce|selce|lce|ymonoretued|tued|ued|leinad|nad|snaissoloc|loc|selcitnac|selcitnac\s+fo\s+elcitnac|nogard\s+leb|seratnac\s+sol\s+ed\s+ratnac|nogard\s+eht\s+dna\s+leb|leb|hcurab|rab|hairaza|soma|sóma|oma|sea|rehtse\s+ot\s+snoitidda|htsedda|htse\s+ot\s+dda|se\s+ot\s+dda|stca|tca|nhoj3|hoj3|oj3|nj3|nhj3|yhtomit2|mit2|it2|snainolasseht2|sseht2|seht2|ht2|ms2|leumas2|mas2|as2|tp2|retep2|tep2|ep2|seebaccam2|ccam2|cam2|am2|sgnik2|gnik2|nik2|ik2|sgk2|nhoj2|hoj2|oj2|nj2|nhj2|sardse2|rdse2|dse2|se2|snaihtniroc2|roc2|oc2|selcinorhc2|norhc2|rhc2|hc2|yhtomit1|mit1|it1|snainolasseht1|sseht1|seht1|ht1|ms1|leumas1|mas1|as1|tp1|retep1|tep1|ep1|seebaccam1|ccam1|cam1|am1|seyer1|seyer2|sgnik1|gnik1|nik1|ik1|sgk1|nhoj1|hoj1|oj1|nj1|nhj1|sardse1|rdse1|dse1|se1|snaihtniroc1|roc1|oc1|selcinorhc1|norhc1|rhc1|hc1|sacinorc1|sacinórc1|sacinorc2|sacinórc1',
			1 => 'yhtomit|mit|it|snainolasseht|sseht|seht|ht|ms|leumas|mas|as|tp|retep|tep|ep|seebaccam|ccam|cam|am|sgnik|gnik|nik|ik|sgk|nhoj|hoj|oj|nj|nhj|sardse|rdse|dse|se|snaihtniroc|roc|oc|selcinorhc|norhc|rhc|hc',
			2 => 'yhtomit|mit|it|snainolasseht|sseht|seht|ht|ms|leumas|mas|as|tp|retep|tep|ep|seebaccam|ccam|cam|am|sgnik|gnik|nik|ik|sgk|nhoj|hoj|oj|nj|nhj|sardse|rdse|dse|se|snaihtniroc|roc|oc|selcinorhc|norhc|rhc|hc',
			3 => 'nhoj|hoj|oj|nj|nhj'),
        1 => array(
			0 => 'pz|cz|sw|bt|ur|or|mr|er|sp|rp|bo|un|mn|en|bn|an|tm|rm|lm|km|vl|kl|el|al|rj|nj|mj|lj|gj|ej|bj|oh|gh|ng|eg|ag|se|ce|td|nd|ad|ca|s2|m2|k2|s1|m1|k1',
			1 => 's|m|k',
			2 => 's|m|k'),
        2 => array(
			0 => 'os|si|xe|ma'
        )
    ));

    private static $prefixes = array(0 => array(
        // Forward
        1 => '(1|one|i|1st|first)(\s+book)?(\s+of)?\s',
        2 => '(2|two|ii|2nd|second)(\s+book)?(\s+of)?\s',
        3 => '(3|three|iii|3rd|third)(\s+book)?(\s+of)?\s'
    ), 1 => array(
        // Reverse
        1 => '\s(fo\s+)?(koob\s+)?(tsrif|ts1|i|eno|1)',
        2 => '\s(fo\s+)?(koob\s+)?(dnoces|dn2|ii|owt|2)',
        3 => '\s(fo\s+)?(koob\s+)?(driht|dr3|iii|eerht|3)'
    ));

    private $_regex = '';
    private function regex()
    {
        //if (empty($this->_regex)) {
        $books = array();
        for ($level = 0; $level <= $this->max_level; $level++) {
            foreach (self::$book_regexes[!$this->forward][$level] as $index => $regexes) {
                if (isset($books[$index])) {
                    $books[$index] .= "|$regexes";
                } else {
                    $books[$index] = $regexes;
                }
            }
        }

        $book_regex = '(' . $books[0] . ')';
        for ($index = 1; $index <= 3; $index++) {
            if ($this->forward) {
                $book_regex .= '|(' . self::$prefixes[!$this->forward][$index] . '(' . $books[$index] . '))';
            } else {
                $book_regex = '((' . $books[$index] . ')' . self::$prefixes[!$this->forward][$index] . ')|' . $book_regex;
            }
        }

        if ($this->add_whole_books) {
            $cv_question = '?';
        } else {
            $cv_question = '';
        }

        if ($this->require_space_before_cv) {
            $space_star = '+';
        } else {
            $space_star = '*';
        }

        // Regex = word boundary, book regex, word boundary, CV regex
        // CV regex = optional period, optional whitespace, number, optional [\s-:,;] ending with number
        if ($this->forward) {
            $this->_regex = "/\b($book_regex)\b(\.?\s$space_star\d([\s-:,;]*\d)*)$cv_question/i";
        } else {
            $this->_regex = "/((\d[\s\-:,;]*)*\d\s$space_star\.?)$cv_question\b($book_regex)\b/i";
        }
        //}

        return $this->_regex;
    }

    public function parse_string($str)
    {
        if (!$this->forward) {
            $str = strrev($str);
        } // Reverse before we parse
        $current_reg_ex = $this->regex();
        $str = preg_replace_callback($current_reg_ex, function ($matches) {
            return $this->replace_cb($matches);
        }, $str);
        if (!$this->forward) {
            $str = strrev($str);
        } // Restore forward order after parsing
        return $str;
    }

    /**
     * Replaces bible references with bible links in a given html string
     * @param string $content
     * @return string
     */
    public function parse_string_html($html)
    {
        return bfox_process_html_text($html, array($this, 'parse_string'));
    }

    public function replace_cb($matches)
    {
        //Older http://biblefox.com/bible/genesis+20%3A1
        //https://www.bible.com/bible/8/GEN.15.4.AMPC
        // 8 => Language english
        // GEN =>prefix genesis
        // 15 => chapter
        // 4 => verse
        //AMPC =>bible type
        $text = $matches[0];
        if (!$this->forward) {
            $text = strrev($text);
        }

        if ($this->forward) {
            if (!empty($matches[2])) {
                $synonym = $matches[2];
            } elseif (!empty($matches[7])) {
                $synonym = '1' . $matches[7];
            } elseif (!empty($matches[12])) {
                $synonym = '2' . $matches[12];
            } elseif (!empty($matches[17])) {
                $synonym = '3' . $matches[17];
            }
        } else {
            if (!empty($matches[19])) {
                $synonym = $matches[19];
            } elseif (!empty($matches[15])) {
                $synonym = $matches[15] . '1';
            } elseif (!empty($matches[10])) {
                $synonym = $matches[10] . '2';
            } elseif (!empty($matches[5])) {
                $synonym = $matches[5] . '3';
            }
        }

        if (!$this->forward) {
            $synonym = strrev($synonym);
        }
        $synonym = strtolower(preg_replace('/\s+/', ' ', $synonym));


        if ($book_id = self::$book_id_lookup[$synonym]) {
            $ref = new BfoxRef;

            if ($this->forward) {
                $cv_str = $matches[18];
            } else {
                $cv_str = strrev($matches[1]);
            }

            $cv_str = ltrim(trim($cv_str), '.');
            if (!empty($cv_str)) {
                self::parse_book_str($ref, $book_id, $cv_str);
            } else {
                $ref->add_whole_book($book_id);
            }

            if ($ref->is_valid()) {
                if (isset($this->ref_array)) {
                    $this->ref_array[] = $ref;
                }
                if (isset($this->total_ref)) {
                    $this->total_ref->add_ref($ref);
                }
                if (isset($this->replace_func)) {
                    $text = call_user_func_array($this->replace_func, array($text, $ref));
                }
                if (isset($this->leftovers)) {
                    $text = '';
                }
            }
        }

        if (!$this->forward) {
            $text = strrev($text);
        }
        return $text;
    }

    /**
     * Add the number part of a bible reference (ie, the 3:16 in John 3:16)
     *
     * @param BfoxRef $ref
     * @param integer $book_id
     * @param string $str
     */
    private static function parse_book_str(BfoxRef &$ref, $book_id, $str)
    {
        // Spaces between numbers count as semicolons
        preg_replace('/(\d)\s+(\d)/', '$1;$2', $str);

        $semis = explode(';', $str);
        foreach ($semis as $semi) {
            $commas = explode(',', $semi);

            $verse_chapter = 0;
            foreach ($commas as $comma) {
                $dash = explode('-', $comma, 2);

                $left = explode(':', $dash[0], 2);
                $ch1 = intval($left[0]);
                $vs1 = !isset($left[1]) ? 0 : intval($left[1]);

                $ch2 = 0;
                $vs2 = 0;
                if (isset($dash[1])) {
                    $right = explode(':', $dash[1], 2);
                    $ch2 = intval($right[0]);
                    $vs2 = isset($right[1]) ? intval($right[1]) : 0;
                }


                // We must have a chapter1
                if (0 != $ch1) {
                    // If verse0 is not 0, but verse1 is 0, we should use chapter1 as verse1, and chapter1 should be 0
                    // This fixes the following type of case: 1:2-3 (1:2-3:0 becomes 1:2-0:3)
                    if ((0 != $vs1) && (0 == $vs2)) {
                        $vs2 = $ch2;
                        $ch2 = 0;
                    }

                    // Whole Chapters (or whole verses)
                    if ((0 == $vs1) && (0 == $vs2)) {
                        $ref->add_whole($book_id, $ch1, $ch2, $verse_chapter);
                    }
                    // Inner Chapters
                    elseif ((0 == $ch2) || ($ch1 == $ch2)) {
                        $verse_chapter = $ch1;
                        $ref->add_inner($book_id, $verse_chapter, $vs1, $vs2);
                    }
                    // Mixed Chapters
                    else {
                        $ref->add_mixed($book_id, $ch1, $vs1, $ch2, $vs2, $verse_chapter);
                        $verse_chapter = $ch2;
                    }
                }
            }
        }
    }
}

/**
 * This function takes some html input ($html) and processes its text using the $func callback.
 *
 * It will skip all html tags and call $func for each chunk of text.
 * The $func function should take the text as its parameter and return the modified text.
 *
 * @param string $html
 * @param function $func
 * @param array $params
 * @return unknown_type
 */
function bfox_process_html_text($html, $func, $params = array())
{
    if (!is_callable($func)) {
        return $html;
    }
    if ($html == false) $html = '';

    $text_start = 0;
    if (is_string($html)) {
        while (1 == preg_match('/<[^<>]*[^<>\s][^<>]*>/', $html, $matches, PREG_OFFSET_CAPTURE, $text_start)) {
            // Store the match data in more readable variables
            $text_end = (int) $matches[0][1];
            $pattern = (string) $matches[0][0];


            $text_len = $text_end - $text_start;
            if (0 < $text_len) {
                // Modify the data with the replacement text
                $replacement = call_user_func_array($func, array_merge(array(substr($html, $text_start, $text_len)), $params));
                $html = substr_replace($html, $replacement, $text_start, $text_len);

                // Skip the rest of the replacement string
                $text_end = $text_start + strlen($replacement);
            }
            $text_start = $text_end + strlen($pattern);
        }

        $text_len = strlen($html) - $text_start;
        if (0 < $text_len) {
            // Modify the data with the replacement text
            $replacement = call_user_func_array($func, array_merge(array(substr($html, $text_start, $text_len)), $params));
            $html = substr_replace($html, $replacement, $text_start, $text_len);
        }
    }
    return $html;
}
