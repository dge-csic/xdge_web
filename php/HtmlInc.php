<?php // encoding="UTF-8"
/**
<h1>HtmlInc, stream methods to get HTML informations (even throw http)</h1>

© 2012, <a href="http://www.algone.net/">Algone</a>, <a href="http://www.cecill.info/licences/Licence_CeCILL-C_V1-fr.html">licence CeCILL-C</a> (LGPL compatible droit français)


<ul>
  <li>2012 [FG] <a onclick="this.href='mailto'+'\x3A'+'glorieux'+'\x40'+'algone.net?subject=[HtmlInc.php] '">Frédéric Glorieux</a></li>
  <li>2012 [VJ] <a onclick="this.href='mailto'+'\x3A'+'jolivet'+'\x40'+'algone.net?subject=[HtmlInc.php] '">Vincent Jolivet</a></li>
</ul>

<p>
In a document point of view, an HTML file is a body and metadatas. Metadatas are useful as an array in memory.
The body could be obtained as a string, or pour in a stream,
useful for slow remote web services, or to cache dynamic contents.
To extract the &lt;body>, a fast way is obtained with a
<a href="http://php.net/manual/en/function.stream-filter-register.php">user stream filter</a>
</p>

<pre>
<?php
// example of usage
$doc=new HtmlInc("http://mySearchEngine.net/results?q=".$_REQUEST['q']);
$slow=true;
?>
<html>
  <head>
    <?php $doc->meta() ?>
  </head>
  <body>
    <div id="header">My header</div>
    <p>My corpus</p>
    <?php
    if($slow) $doc->body(); // direct output to screen
    else echo $doc->body(''); // get body as string
    ?>
    <div id="header">My footer</div>
  </body>
</html>
</pre>

*/
class HtmlInc {
  // the requested uri (maybe file)
  public $uri;
  // all html
  public $html;
  // just meta tags,
  public $meta;
  // metas as an array
  public $props;
  // the filename
  public $name;
  // A short label for the resource
  public $label;
  // A longer title
  public $title;
  // boolean, if remote
  public $http;
  // if an error when instantiate
  public $error;
  /**
   * Contructor, loads the <head>, doesn't store the body by default.
   */
  public function __construct($uri, $html=false) {
    if($html) $this->html=$html;
    // if no uri, let user instantiate object and use it like he wants
    if (!$uri) return;
    $this->uri=$uri;
    $this->name=$this->label=current(explode('.', basename($uri)));
    if(strpos($uri, "http://") === 0) return $this->http=true;
    if (!is_file($uri)) {
      $this->error='<p class="error">Page momentanément indisponible. <!-- '.$uri.' --></p>';
      return false;
    }
  }
  /**
   * Load html, lazzy (only if needed)
   */
  public function html($html=false) {
    if ($this->html) return $this->html;
    if (!$this->uri) {
      $this->error='<p class="error">Erreur interne (pas de fichier demandé).</p>';
      return null;
    }
    $this->html=file_get_contents($this->uri);
    return $this->html;
  }
  /**
Parse head, lazzy.

  <p>
  Extractions has not the limitation of <a href="http://fr.php.net/manual/en/function.get-meta-tags.php">get_meta_tags()</a>
  « PHP uses a native function to parse the input, so a Mac file won't work on Unix …
  Special characters in the value of the name property are substituted with '_' …
  If two meta tags have the same name, only the last one is returned ».
  Real work with metas needs to be system independant, full unicode, repeatable,
  see for example <a href="http://dublincore.org/documents/dc-html/">Dublin Core in HTML</a>.
  </p>
  <p>The structure of the array as three levels.</p>
  <ul>
    <li>level 1 : key of the property : meta/@name | link/@rel (with namespace prefix stripped dc.title becomes title)</li>
    <li>level 2 : index of value in declaration order</li>
    <li>level 3 : values in PHP "PDO::FETCH_BOTH" style : « returns an array indexed by both column name and 0-indexed column number ».
      <ul>
        <li>String value (0=>, "string"=>) : meta/@content | link/@title.</li>
        <li>Uri value (1=>, "href"=>) : link/@href</li>
      </ul>
  </ul>
  <pre>
<head>
  <title>Article 13. III. Paix de Longjumeau. Édit de Paris. Édits de pacification.</title>
  <meta name="label" content="III, 13"/>
  <link rel="dc:isPartOf" href="." title="Édits de pacification"/>
  <link rel="DC.isPartOf" href="edit_03" title="III. Paix de Longjumeau. Édit de Paris"/>
</head>


Array (
  [title] => Array (
    [0] => Array (
      [0] => Article 13. III. Paix de Longjumeau. Édit de Paris. Édits de pacification.
      [string] => Article 13. III. Paix de Longjumeau. Édit de Paris. Édits de pacification.
  )
  )
  [label] => Array (
    [0] => Array (
      [0] => III, 13
      [string] => III, 13
    )
  )

  [isPartOf] => Array (
    [0] => Array (
      [0] => Édits de pacification
      [string] => Édits de pacification
      [1] => .
      [uri] => .
    )
    [1] => Array (
      [0] => III. Paix de Longjumeau. Édit de Paris
      [string] => III. Paix de Longjumeau. Édit de Paris
      [1] => edit_03
      [uri] => edit_03
    )
  )
)


  </pre>

   */
  public function head() {
    if ($this->meta) return $this->meta;
    $head=self::headSub($this->html());
    $this->props=array();
    // keep title in memory
    $title=array("");
    preg_match('/<title>([^<]+)<\/title>/i', $head, $title);
    if (isset($title[1])) $this->props['title'][]=array(0=>$title[1], "string"=>$title[1]);
    // grab all tags candidates
    preg_match_all("/<(meta|link)[^>]+>/i", $head, $meta, PREG_PATTERN_ORDER);
    // filter tags kown to not be metas
    $meta=preg_grep( "/stylesheet|http-equiv|icon/", $meta[0], PREG_GREP_INVERT);
    // loop on meta to populate the array
    foreach ($meta as $line) {
      preg_match('/(name|rel)="([^"]+)"/i', $line, $key);
      preg_match('/(content|title)="([^"]+)"/i', $line, $string);
      preg_match('/(scheme|href)="([^"]+)"/i', $line, $uri);
      if (!isset($key[2])) continue;
      // strip namespace prefix of property
      if ($pos=strpos($key[2], '.')) $key[2]=substr($key[2], $pos+1);
      if ($pos=strpos($key[2], ':')) $key[2]=substr($key[2], $pos+1);
      // all props supposed repeat
      if(isset($uri[2]) && isset($string[2])) $this->props[$key[2]][]=array(0=>$string[2], "string"=>$string[2], 1=>$uri[2], "uri"=>$uri[2]);
      else if(isset($uri[2])) $this->props[$key[2]][]=array(0=>$uri[2], "uri"=>$uri[2]);
      else if(isset($string[2])) $this->props[$key[2]][]=array(0=>$string[2], "string"=>$string[2]);
    }
    // rebuild a clean meta block ready to include in HTML
    $this->meta="\n    " . @$title[0] . "\n    " . implode("\n    ", $meta);
    return $this->meta;
  }
  /**
   * Efficient cut of head
   */
  public static function headSub($html) {
    if (!$start=stripos($html, "<head")) return "";
    $start=strpos($html, ">", $start)+1;
    $to=stripos($html, "</head>");
    if ($to) return substr($html, $start, $to - $start);
    else return substr($html, $start);
  }
  /**
   * Cut an html string to give only a body
   */
  public static function bodySub($html) {
    if (!$start=stripos($html, "<body")) return $html;
    $start=strpos($html, ">", $start)+1;
    $to=stripos($html, "</body>");
    if ($to) return substr($html, $start, $to - $start);
    else return substr($html, $start);
  }
  public function props() {
    $this->head();
    return $this->props;
  }
  /**
   * Print meta to the stream
   * $out (default) : output stream like a print or an echo
   * $out resource : output stream to the resource (file or something else)
   * $out "" : output as a String
   */
  public function meta($out=null) {
    $this->head();
    if (is_string($out)) return $this->meta;
    if (!is_resource($out)) $out=fopen("php://output", "w");
    fwrite($out, $this->meta);
  }

  /**
   * Fix html body of some problems of wild html
   */
  public static $fixhtml= array(
    '/<\/?font[^>]*>/i'=>'',
    '/ class="western"/i'=>'',
    '/ class="([^"]+)-western"/i' => ' class="$1"',
    '/&nbsp;/' => ' ',
    '/(<sup><\/?a[^>]*>)<sup>/i' => '$1', // fix specifique OOo
    '/<\/sup>(<\/a><\/sup>)/i' => '$1', // fix specifique OOo
    // '/ style="[^"]*"/i' => '', // pb for width
  );

  /**
   * Output body to the stream passed as argument
   * $out (default) : output stream like a print or an echo
   * $out resource : output stream to the resource (file or something else)
   * $out "" : output as a String
   */
  public function body($out=null) {
    // error already given, return it
    if ($this->error) {
      echo $this->error,' <!--',$this->uri, '-->';
      return;
    }
    $html=self::bodySub($this->html());
    $html=preg_replace(array_keys(self::$fixhtml), array_values(self::$fixhtml), $html);
    // if caller wants a string, like body(""), send just a string with no output
    if (is_string($out));
    else if (!$out) $out=fopen("php://output", "w");
    if (is_resource($out)) fwrite($out, $html);
    return $html;
  }

  /**
   * Try to find a not too bad short title.
   */
  public function label() {
    if (isset($this->props['label'])) return $this->props['label'][0][0];
    /* // ?? what TODO there, load a body for a label ?
    if (!$this->label) {
      if (!$this->body) $this->body();
      preg_match("/<h1[^>]*>([^<]+)<\/h1>/", $this->body, $label);
      if (isset($label[1])) $this->label=$label[1];
    }*/
    if (isset($this->props['title'])) return $this->props['title'][0][0];
    return $this->label;
  }
}


/*
Stream filter to extract <body>.
too clever to work efficiently

      if (!is_resource($out)) $out=fopen("php://output", "w");
      // open source
      $stream=fopen($this->uri , "r");
      // if not fragment, append the body filter
      if(!$this->fragment) stream_filter_append($stream, "body.sertpasencore");
      $copied=stream_copy_to_stream($stream, $out);
      fclose($stream);
      // nothing found (probably no <body> tag) retry
      if (!$copied) print file_get_contents($this->uri);
class Filter_body extends php_user_filter {
  // flag, start open body found
  private $body_lt;
  // flag, end open body found
  private $body_gt;
  // flag, end body found
  private $body_slash;
  // buffer size
  private $len=10;
  // buffer
  private $last="          ";
  //
  function filter($in, $out, &$consumed, $closing) {
    while ($bucket = stream_bucket_make_writeable($in)) {
      // keep last chars in memory
      $last=substr(str_repeat(" ",$this->len).$bucket->data, 0-$this->len);
      // <body
      if (!$this->body_lt && ($pos=stripos($this->last.$bucket->data, '<body')) !== FALSE) {
        $bucket->data = "\n\$pos\n".substr($this->last.$bucket->data, $pos);
        $this->body_lt=true;
      }
      // passer du head
      else if(!$this->body_lt) {
        $bucket->data ="";
      }
      // (<body.*)>
      if ($this->body_lt && !$this->body_gt && ($pos=strpos($bucket->data, '>')) !== FALSE) {
        $bucket->data = substr($bucket->data, $pos+1);
        $this->body_gt=true;
      }
      // </body>
      if($this->body_gt && !$this->body_slash && ($pos=stripos($this->last.$bucket->data, '</body>')) !== FALSE) {
        $bucket->data = substr($bucket->data, 0, $pos - $this->len);
        $this->body_slash=true;
      }
      $this->last=$last;
      $consumed += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }
    return PSFS_PASS_ON;
  }
}
// Register the filter here, be careful, let .*, because of a bug in php 5.2
stream_filter_register("body.*", "Filter_body");



*/


?>
