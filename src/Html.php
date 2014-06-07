<?php
class Html {
  /**
   * Copied from D8.
   */
  public static function normalize($html) {
    $document = static::load($html);
    return static::serialize($document);
  }

  /**
   * Copied from D8.
   */
  public static function load($html) {
    $document = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
<body>!html</body>
</html>
EOD;
    // PHP's \DOMDocument serialization adds straw whitespace in case the markup
    // of the wrapping document contains newlines, so ensure to remove all
    // newlines before injecting the actual HTML body to process.
    $document = strtr($document, array("\n" => '', '!html' => $html));

    $dom = new \DOMDocument();
    // Ignore warnings during HTML soup loading.
    @$dom->loadHTML($document);

    return $dom;
  }

  /**
   * Copied from D8.
   */
  public static function serialize(\DOMDocument $document) {
    $body_node = $document->getElementsByTagName('body')->item(0);
    $html = '';

    foreach ($body_node->getElementsByTagName('script') as $node) {
      $this->escapeCdataElement($node);
    }
    foreach ($body_node->getElementsByTagName('style') as $node) {
      $this->escapeCdataElement($node, '/*', '*/');
    }
    foreach ($body_node->childNodes as $node) {
      $html .= $document->saveXML($node);
    }
    return $html;
  }


  /**
   * Proposed new truncate function.
   *
   * Uses regex matching and array manipulation.
   */
  public static function truncate($text, $maxlength, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
    $text = static::normalize($text);

    preg_match_all('/<[^>]++>|[^<>\s]++/', $text, $tokens);

    $counter = 0;
    $newtext = array();
    foreach ($tokens[0] as $i => $token) {
      if (mb_substr($token, 0, 1, 'utf-8') === '<') {
        $newtext[] = $token;
        continue;
      }
      $counter += mb_strlen(html_entity_decode($token));
      if ($counter > $maxlength) {
        if (!$wordsafe) {
          $delta = $counter - $maxlength;
          $fragment = mb_substr($token, 0, $delta);
          $newtext[] = $fragment;
        }
        break;
      }
      $newtext[] = $token;
    }
    $text = implode('', $newtext);
    $text = static::normalize($text);
    return $text;
  }

  public static function truncate2Measure($str) {
    return mb_strlen(html_entity_decode(strip_tags($str)));
  }

  public static function substr($text, $start, $length) {
    return mb_substr($text, $start, $length, 'utf-8');
  }

  /**
   * Proposed new truncate function.
   *
   * Uses a strlen delta and a loop to work forwards to a minimum trim.
   */
  public static function truncate2($text, $maxlength, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
    $cutlength = $maxlength;

    while (0 < $delta = $maxlength - static::truncate2Measure(static::substr($text, 0, $cutlength))) {
        $cutlength += $delta;
    }

    if ($wordsafe) {
      do {
        // If the letters before and after the cutlength are not whitespace, we
        // almost cut a word! backtrack!
        $before = static::substr($text, $cutlength - 1, 1);
        $after = static::substr($text, $cutlength, 1);

        $is_cutting_word = !ctype_space($before) && !ctype_space($after);
        if ($is_cutting_word) {
          $cutlength--;
        }
      }
      while ($is_cutting_word);
    }

    // Do the trimming.
    $newtext = static::substr($text, 0, $cutlength, 'utf-8');

    // Clean up hanging whitespace if we're wordsafe.
    if ($wordsafe) {
      $newtext = rtrim($newtext);
    }
    return $newtext;
  }
}
