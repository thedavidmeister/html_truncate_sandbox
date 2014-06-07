<?php
class TruncateTest extends PHPUnit_Framework_Testcase {

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
      // array(
      //   't' => 'foo&nbsp;bar',
      //   'l' => 4,
      //   'e' => 'foo&nbsp;',
      //   'ws' => FALSE,
      //   'm' => 'preserve &nbsp; as a single whitespace character when cut, wordsafe off',
      // ),
      // array(
      //   't' => 'foo&nbsp;bar',
      //   'l' => 4,
      //   'e' => 'foo',
      //   'ws' => TRUE,
      //   'm' => 'remove malformed &nbsp; as a whitespace character when cut, wordsafe on',
      // ),
    );

    foreach ($tests as $test) {
      $this->assertEquals($test['e'], Html::truncate2($test['t'], $test['l'], $test['ws']), $test['m']);
    }
  }

}
