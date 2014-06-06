<?php
class TruncateTest extends PHPUnit_Framework_Testcase {

  /**
   * Copied from D8.
   */
  public function normalize($html) {
    $document = $this->load($html);
    return $this->serialize($document);
  }

  /**
   * Copied from D8.
   */
  public function load($html) {
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
  public function serialize(\DOMDocument $document) {
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
   */
  public function truncate($text, $maxlength, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
    $text = $this->normalize($text);
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
    $text = $this->normalize($text);
    return $text;
  }


  /**
   * Tests for truncate with word safe disabled and no HTML.
   * @return [type] [description]
   */
  public function testCanCountString() {
    $tests = array(
      array(
        't' => 'foo bar',
        'l' => 5,
        'e' => 'foo b',
        'ws' => FALSE,
        'm' => 'basic foo bar, wordsafe off',
      ),
      array(
        't' => 'foo' . PHP_EOL . 'bar',
        'l' => 5,
        'e' => 'foo' . PHP_EOL . 'b',
        'ws' => FALSE,
        'm' => 'foobar with newline, wordsafe off',
      ),
      array(
        't' => 'foo bar',
        'l' => 5,
        'e' => 'foo',
        'ws' => TRUE,
        'm' => 'basic foo bar, wordsafe on',
      ),
      array(
        't' => 'foo' . PHP_EOL . 'bar',
        'l' => 5,
        'e' => 'foo',
        'ws' => TRUE,
        'm' => 'foobar with newline, wordsafe on',
      ),
      array(
        't' => 'foobar',
        'l' => 4,
        'e' => 'foob',
        'ws' => FALSE,
        'm' => 'basic foobar, wordsafe off',
      ),
      array(
        't' => 'CaFÉ bar',
        'l' => 5,
        'e' => 'CaFÉ ',
        'ws' => FALSE,
        'm' => 'CaFÉ, wordsafe off'
      ),
    );

    foreach ($tests as $test) {
      $this->assertEquals($test['e'], $this->truncate($test['t'], $test['l'], $test['ws']), $test['m']);
    }
  }

}
