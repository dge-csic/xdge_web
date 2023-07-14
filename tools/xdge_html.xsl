<?xml version="1.0" encoding="UTF-8"?>
<!--

Transform XDGE in html.

<ul>
  <li>[FG] <a href="#" onmouseover="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'algone.net'">Frédéric Glorieux</a></li>
  <li>[ST] Sabine Thuillier</li>
</ul>

-->
<xsl:transform exclude-result-prefixes="tei" extension-element-prefixes="exslt php date" version="1.1" xmlns="http://www.w3.org/1999/xhtml" xmlns:date="http://exslt.org/dates-and-times" xmlns:exslt="http://exslt.org/common" xmlns:php="http://php.net/xsl" xmlns:tei="http://www.tei-c.org/ns/1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- do not strip spaces -->
  <xsl:output encoding="UTF-8" indent="yes" method="xml" omit-xml-declaration="yes"/>
  <!-- shared templates -->
  <xsl:param name="this">xdge_html.xsl</xsl:param>
  <!-- for direct transformation to get a relative link to css  -->
  <!-- Generated date -->
  <xsl:param name="date">
    <xsl:choose>
      <xsl:when test="function-available('date:date')">
        <xsl:value-of select="date:date()"/>
      </xsl:when>
      <xsl:otherwise>2022</xsl:otherwise>
    </xsl:choose>
  </xsl:param>
  <!-- folder for theme -->
  <xsl:param name="theme">../xdge_web/theme/</xsl:param>
  <!-- Share the same html prolog -->
  <xsl:template name="prolog">
    <xsl:text disable-output-escaping="yes"><![CDATA[<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">]]></xsl:text>
    <xsl:comment>
      <xsl:value-of select="$this"/>
      <xsl:text> — </xsl:text>
      <xsl:value-of select="$date"/>
    </xsl:comment>
  </xsl:template>
  <xsl:output doctype-public="html" encoding="UTF-8" indent="yes"/>
  <!-- Corps HTML -->
  <xsl:template match="tei:TEI">
    <xsl:comment>
      <xsl:value-of select="$this"/>
      <xsl:text> — </xsl:text>
      <xsl:value-of select="$date"/>
    </xsl:comment>
    <html>
      <head>
        <meta charset="UTF-8"/>
        <title>XDGE</title>
        <link href="{$theme}xdge_html.css" rel="stylesheet"/>
      </head>
      <body class="article">
        <xsl:apply-templates/>
      </body>
    </html>
  </xsl:template>
  <!-- Nothing done with that for now -->
  <xsl:template match="tei:lcStart | tei:lcEnd | tei:llccStart | tei:llccEnd | tei:addStart | tei:addEnd | tei:delStart | tei:delEnd  "/>
  <!-- Header, one day -->
  <xsl:template match="tei:teiHeader"/>
  <!-- Cross -->
  <xsl:template match="tei:text | tei:body">
    <xsl:apply-templates/>
  </xsl:template>
  <!-- Article -->
  <xsl:template name="prevnext">
    <xsl:text>&#10;        </xsl:text>
    <nav class="prevnext">
      <xsl:text>&#10;          </xsl:text>
      <a class="prev">
        <xsl:for-each select="preceding-sibling::tei:entry[1]">
          <xsl:attribute name="href">
            <xsl:call-template name="id"/>
          </xsl:attribute>
            <xsl:text>◀ </xsl:text>
            <xsl:value-of select="tei:form/tei:orth"/>
          <xsl:text> </xsl:text>
        </xsl:for-each>
        <xsl:if test="not(preceding-sibling::tei:entry)"> </xsl:if>
      </a>
      <xsl:text>&#10;          </xsl:text>
      <a class="next">
        <xsl:for-each select="following-sibling::tei:entry[1]">
          <xsl:attribute name="href">
            <xsl:call-template name="id"/>
          </xsl:attribute>
          <xsl:value-of select="tei:form/tei:orth"/>
          <xsl:text> ▶</xsl:text>
        </xsl:for-each>
        <xsl:if test="not(following-sibling::tei:entry)"> </xsl:if>
      </a>
      <xsl:text>&#10;        </xsl:text>
    </nav>
    <xsl:text>&#10;        </xsl:text>
  </xsl:template>
  <xsl:template match="tei:entry">
    <div class="entry-cont">
      <xsl:call-template name="prevnext"/>
      <div class="row">
        <article class="entry {@type}">
          <xsl:attribute name="id">
            <xsl:call-template name="id"/>
          </xsl:attribute>
          <xsl:apply-templates select="tei:form"/>
          <div class="body">
            <xsl:apply-templates select="node()[not(self::tei:form)][not(self::tei:etym)][not(self::tei:bibl)]"/>
          </div>
          <xsl:if test="tei:bibl | tei:etym">
            <footer>
              <xsl:apply-templates select="tei:bibl | tei:etym"/>
            </footer>
          </xsl:if>
        </article>
        <nav class="entry-nav">
          <xsl:if test="tei:sense[tei:num]">
            <ul>
              <xsl:apply-templates mode="toc" select="tei:sense[tei:num]"/>
            </ul>
          </xsl:if>
        </nav>
      </div>
    </div>
  </xsl:template>
  <!-- -->
  <xsl:template match="tei:sense" mode="toc">
    <li class="sense">
      <a>
        <xsl:attribute name="href">
          <xsl:text>#</xsl:text>
          <xsl:call-template name="id"/>
        </xsl:attribute>
        <xsl:apply-templates select="node()[1]" mode="next"/>
      </a>
      <xsl:if test="tei:sense[tei:num]">
        <ul>
          <xsl:apply-templates mode="toc" select="tei:sense[tei:num]"/>
        </ul>
      </xsl:if>
    </li>
  </xsl:template>
  <xsl:template match="tei:milestone[@unit = 'label']"/>
  <!-- go next -->
  <xsl:template match="node()" mode="next">
    <xsl:choose>
      <xsl:when test="self::tei:milestone[@unit = 'label']"/>
      <xsl:when test="self::tei:cit"/>
      <xsl:when test="self::tei:sense"/>
      <xsl:when test="self::tei:bibl"/>
      <xsl:when test="self::tei:num">
        <xsl:value-of select="."/>
        <xsl:variable name="clast" select="substring(normalize-space(.), string-length(normalize-space(.)))"/>
        <xsl:if test="not(contains(').—', $clast))">.</xsl:if>
        <xsl:apply-templates select="following-sibling::node()[1]" mode="next"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:apply-templates select=".">
          <xsl:with-param name="mode">toc</xsl:with-param>
        </xsl:apply-templates>
        <xsl:apply-templates select="following-sibling::node()[1]" mode="next"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <!-- Sense -->
  <xsl:template match="tei:sense">
    <section class="sense {@rend}">
      <xsl:attribute name="id">
        <xsl:call-template name="id"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </section>
  </xsl:template>
  <!-- Entry head -->
  <xsl:template match="tei:entry / tei:form">
    <header>
      <section class="form form1">
        <xsl:for-each select="*[not(self::tei:form)]">
          <xsl:choose>
            <xsl:when test="position() != 1">
              <xsl:text>, </xsl:text>
            </xsl:when>
          </xsl:choose>
          <xsl:apply-templates select="."/>
        </xsl:for-each>
      </section>
      <xsl:text>&#10;          </xsl:text>
      <xsl:apply-templates select="tei:form"/>
    </header>
  </xsl:template>
  <!-- Grammatical information, with a comma before (but not a new line, the reason of xsl:text)  -->
  <xsl:template match="tei:form / tei:gramGrp">
    <span class="gramGrp">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <!-- Headword -->
  <xsl:template match="tei:orth">
    <xsl:variable name="element">
      <xsl:choose>
        <xsl:when test="@type ='lemma'">strong</xsl:when>
        <xsl:when test="@type ='latin'">em</xsl:when>
        <xsl:otherwise>span</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:element name="{$element}">
      <xsl:attribute name="class">
        <xsl:value-of select="normalize-space(concat('orth ', @lang, ' ', @type))"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>
  <!-- Numérotation -->
  <xsl:template match="tei:num">
    <xsl:text> </xsl:text>
    <xsl:choose>
      <xsl:when test=". = ';'">
        <b class="num">•</b>
      </xsl:when>
      <!-- pour les niveaux supérieurs sans contenu, type "A I", ne pas sauter de ligne avant le num enfant -->
      <xsl:when test="@type">
        <b class="num start">
          <xsl:apply-templates/>
        </b>
      </xsl:when>
      <xsl:otherwise>
        <b class="num">
          <xsl:apply-templates/>
        </b>
        <xsl:text> </xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <!--
    <!-\- Link  -\->
  <xsl:template match="tei:ref">
    <a>
      <xsl:attribute name="href">
        <xsl:if test="$this = 'lmpg_html.xsl'">#</xsl:if>
        <xsl:choose>
          <xsl:when test="function-available('php:function')">
            <!-\- Do not forget to pass a string and not a node to php function -\->
            <xsl:value-of select="php:function('urlencode', string(@target))"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@target"/>
          </xsl:otherwise>
        </xsl:choose>        
      </xsl:attribute>
      <xsl:apply-templates/>
    </a>
  </xsl:template> 
  -->
  <!-- Cross-references -->
  <xsl:template match="tei:xr">
    <xsl:param name="mode"/>
    <xsl:apply-templates>
      <xsl:with-param name="mode" select="$mode"/>
    </xsl:apply-templates>
  </xsl:template>
  <xsl:template match="tei:ref">
    <xsl:param name="mode"/>
    <xsl:choose>
      <xsl:when test="$mode = 'toc'">
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:otherwise>
        <a>
          <xsl:choose>
            <xsl:when test="@target">
              <xsl:attribute name="href">
                <xsl:value-of select="@target"/>
              </xsl:attribute>
            </xsl:when>
          </xsl:choose>
          <xsl:apply-templates/>
        </a>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <!-- The sense(s) container, should be mixed text -->
  <xsl:template match="tei:dictScrap">
    <!-- Should avoid some problems of new line -->
    <xsl:choose>
      <xsl:when test="count(*)=1 and not(text()[normalize-space(.) != ''])">
        <xsl:apply-templates select="*"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:apply-templates select="node()"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:text> </xsl:text>
  </xsl:template>
  <!-- normal segment in italic -->
  <xsl:template match="tei:note">
    <xsl:text> </xsl:text>
    <span class="{local-name()}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <!-- Translation of lemmas -->
  <xsl:template match="tei:def">
    <dfn class="def">
      <xsl:apply-templates/>
    </dfn>
  </xsl:template>
  <!-- Translation (not used in dge, only in lmpg). -->
  <!-- <xsl:template match="tei:gloss">
    <dfn>
      <xsl:apply-templates/>
    </dfn>
     A comma after when strictly followed by another translation (not a text node() like “o”).
    <xsl:variable name="next" select="following-sibling::node()[normalize-space(.)!=''][1]"/>
    <xsl:choose>
      <xsl:when test="local-name($next) = 'gloss'">
        <xsl:text>, </xsl:text>
      </xsl:when>
    </xsl:choose>
  </xsl:template>-->
  <!-- higher level -->
  <!-- Citations   -->
  <xsl:template match="tei:cit">
    <!-- Temporaire, .cit display:inline 
    <br/>-->
    <div class="{local-name()}">
      <xsl:attribute name="id">
        <xsl:call-template name="id"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  <!-- Not used for the moment in dge -->
  <xsl:template match="tei:lbl">
    <span class="{local-name}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <!-- Greek context quoted -->
  <xsl:template match="tei:quote">
    <q>
      <xsl:attribute name="class">
        <xsl:value-of select="normalize-space(concat('quote ', @xml:lang))"/>
      </xsl:attribute>
      <xsl:if test="@xml:lang">
        <xsl:attribute name="lang">
          <xsl:value-of select="@xml:lang"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:apply-templates/>
    </q>
  </xsl:template>
  <!-- usage mark -->
  <xsl:template match="tei:usg">
    <xsl:param name="mode"/>
    <span class="usg">
      <xsl:apply-templates>
        <xsl:with-param name="mode" select="$mode"/>
      </xsl:apply-templates>
    </span>
  </xsl:template>
  <!-- foreign word -->
  <xsl:template match="tei:foreign">
    <em class="{@xml:lang}">
      <xsl:apply-templates/>
    </em>
  </xsl:template>
  <!-- Greek, not in italic, even as foreign word in flow -->
  <xsl:template match="tei:foreign[@xml:lang='grc']">
    <span class="grc">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <!-- italic -->
  <xsl:template match="tei:hi[substring(@rend, 1, 1)='i']">
    <i>
      <xsl:apply-templates/>
    </i>
  </xsl:template>
  <xsl:template match="tei:hi[not(@rend)]">
    <i>
      <xsl:apply-templates/>
    </i>
  </xsl:template>
  <!-- Exposant -->
  <xsl:template match="tei:hi[@rend='sup']">
    <sup>
      <xsl:apply-templates/>
    </sup>
  </xsl:template>
  <!-- Indice -->
  <xsl:template match="tei:hi[@rend='sub']">
    <sub>
      <xsl:apply-templates/>
    </sub>
  </xsl:template>
  <!-- Different sections with labels: etymologia, morphological informations, Dmic -->
  <xsl:template match="tei:form//tei:form | tei:etym ">
    <xsl:variable name="class" select="normalize-space(concat(local-name(), ' ', @type))"/>
    <section class="{$class}">
      <div>
        <span>
          <xsl:choose>
            <xsl:when test="self::tei:etym">Etimología</xsl:when>
            <xsl:when test="@type='alolema'">Alolema(s)</xsl:when>
            <xsl:when test="@type='grafia'">Grafía</xsl:when>
            <xsl:when test="@type='prosodia'">Prosodia</xsl:when>
            <xsl:when test="@type='morfologia'">Morfología</xsl:when>
          </xsl:choose>
        </span>
        <xsl:text>: </xsl:text>
        <xsl:apply-templates/>
      </div>
    </section>
  </xsl:template>
  <xsl:template match="tei:bibl[@type='dmic']">
    <div class="dmic">
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  <xsl:template match="tei:bibl[@type='dmic']/tei:title">
    <span>
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <xsl:template match="tei:title">
    <cite>
      <xsl:apply-templates/>
    </cite>
  </xsl:template>
  <xsl:template match="tei:bibl">
    <span class="bibl" id="{@xml:id}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <xsl:template match="tei:biblScope">
    <span class="{local-name()}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <xsl:template match="tei:author">
    <!-- One day, a link ? -->
    <span class="{local-name()}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <xsl:template match="tei:date">
    <time>
      <xsl:apply-templates/>
    </time>
  </xsl:template>
  <xsl:template match="tei:pc">
    <b class="{local-name()}">
      <xsl:apply-templates/>
    </b>
  </xsl:template>
  <xsl:template match="tei:placeName">
    <span class="{local-name()}">
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <!-- <*>, default model for unknown tag -->
  <xsl:template match="*">
    <div>
      <xsl:call-template name="tag"/>
      <xsl:apply-templates/>
      <font color="red">
        <xsl:text>&lt;/</xsl:text>
        <xsl:value-of select="name()"/>
        <xsl:text>&gt;</xsl:text>
      </font>
    </div>
  </xsl:template>
  <!-- open tag with atts -->
  <xsl:template name="tag">
    <font color="red">
      <xsl:text>&lt;</xsl:text>
      <xsl:value-of select="name()"/>
      <xsl:for-each select="@*">
        <xsl:text> </xsl:text>
        <xsl:value-of select="name()"/>
        <xsl:text>="</xsl:text>
        <xsl:value-of select="."/>
        <xsl:text>"</xsl:text>
      </xsl:for-each>
      <xsl:text>&gt;</xsl:text>
    </font>
  </xsl:template>

  <!-- Identify an article or components with the rule
article
article_cit{number()} : for citations
article_{sense/@n}    :  
  -->
  <xsl:template name="id">
    <!--
    <xsl:value-of select="ancestor-or-self::tei:entry[1]/tei:form/tei:orth[@type='lemma']"/>
    <xsl:variable name="cit">
      <xsl:for-each select="ancestor-or-self::tei:cit[1]">
        <xsl:number level="any" from="tei:entry"/>
      </xsl:for-each>
    </xsl:variable>
    -->
    <xsl:choose>
      <xsl:when test="@xml:id">
        <xsl:value-of select="@xml:id"/>
      </xsl:when>
      <xsl:when test="ancestor-or-self::tei:sense">
        <xsl:value-of select="ancestor-or-self::tei:entry[1]/@xml:id"/>
        <xsl:text>_</xsl:text>
        <xsl:for-each select="ancestor-or-self::tei:sense">
          <xsl:choose>
            <xsl:when test="tei:num">
              <xsl:value-of select="tei:num"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:text>.</xsl:text>
              <xsl:number/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:for-each>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
</xsl:transform>
