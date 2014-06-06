<?php
class TruncateTest extends PHPUnit_Framework_Testcase {

  public function normalize($html) {
    $document = $this->load($html);
    return $this->serialize($document);
  }

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

  public function truncate($text, $maxlength) {
    preg_match_all('/<[^>]++>|[^<>\s]++/', $text, $tokens);

    $counter = 0;
    $newtext = array();
    foreach ($tokens[0] as $token) {
      if (mb_substr($token, 0, 1, 'utf-8') === '<') {
        $newtext[] = $token;
        continue;
      }
      $counter += strlen(html_entity_decode($token));
      if ($counter > $maxlength) {
        break;
      }
      $newtext[] = $token;
    }
    return implode('', $newtext);
  }


  public function testCanCountString() {
    $tests = array(
      array(
        't' => 'foo bar',
        'l' => 5,
        'e' => 'foo b',
      ),
      array(
        't' => 'foobar',
        'l' => 5,
        'e' => 'fooba',
      ),
    );

    foreach ($tests as $test) {
      $this->assertEquals($this->truncate($test['t'], $test['l']), $test['e']);
    }
  }
}
