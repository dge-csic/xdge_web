<?xml version="1.0" encoding="UTF-8"?>
<!--

Transform XDGE in html.

<ul>
  <li>[FG] <a href="#" onmouseover="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'algone.net'">Frédéric Glorieux</a></li>
  <li>[ST] Sabine Thuillier</li>
</ul>

-->
<xsl:transform version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.w3.org/1999/xhtml" xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="tei" xmlns:exslt="http://exslt.org/common" xmlns:php="http://php.net/xsl"
  xmlns:date="http://exslt.org/dates-and-times" extension-element-prefixes="exslt php date">
  <!-- shared templates -->
  <xsl:param name="this">xdge_html.xsl</xsl:param>
  <!-- for direct transformation to get a relative link to css  -->
  <xsl:param name="context">../../</xsl:param>
  <!-- Generated date -->
  <xsl:param name="date">
    <xsl:choose>
      <xsl:when test="function-available('date:date')">
        <xsl:value-of select="date:date()"/>
      </xsl:when>
      <xsl:otherwise>2011</xsl:otherwise>
    </xsl:choose>
  </xsl:param>
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
        <xsl:call-template name="head"/>
      </head>
      <body class="article">
        <xsl:apply-templates/>
      </body>
    </html>
  </xsl:template>
  <!-- Shared head content in files -->
  <xsl:template name="head">
    <xsl:param name="context" select="$context"/>
    <link rel="stylesheet" href="{$context}lib/dge.css"/>
  </xsl:template>
  <!-- Header, one day -->
  <xsl:template match="tei:teiHeader"/>
  <!-- Cross -->
  <xsl:template match="tei:text | tei:body">
    <xsl:apply-templates/>
  </xsl:template>
  <!-- Article -->
  <xsl:template match="tei:entry">
    <article class="entry">
      <xsl:attribute name="id">
        <xsl:call-template name="id"/>
      </xsl:attribute>
      <nav class="prevnext">
       <xsl:text> </xsl:text>
        <xsl:for-each select="preceding-sibling::tei:entry[1]">
          <a class="prev">
            <xsl:attribute name="href">
              <xsl:call-template name="id"/>
            </xsl:attribute>
            <xsl:text>&lt; </xsl:text>
            <xsl:value-of select="tei:form/tei:orth"/>
          </a>
          <xsl:text> </xsl:text>
        </xsl:for-each>
        <xsl:for-each select="following-sibling::tei:entry[1]">
          <xsl:text> </xsl:text>
          <a class="next">
            <xsl:attribute name="href">
              <xsl:call-template name="id"/>
            </xsl:attribute>
            <xsl:value-of select="tei:form/tei:orth"/>
            <xsl:text> &gt;</xsl:text>
          </a>
          <xsl:text> </xsl:text>
        </xsl:for-each>
      </nav>
      <xsl:apply-templates select="node()[not(self::tei:xr)][not(self::tei:etym)][not(self::tei:bibl)]"/>
      <xsl:if test="tei:bibl[@type='dmic'] | tei:etym">
        <footer>
          <xsl:apply-templates select="tei:bibl[@type='dmic'] | tei:etym"/>
        </footer>
      </xsl:if>
      <xsl:apply-templates select="tei:xr"/>
    </article>
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
    <header class="form">
      <xsl:apply-templates select="node()[not(self::tei:form)]"/>
      <xsl:choose>
        <xsl:when test="tei:form">
          <div class="form">
            <xsl:apply-templates select="tei:form"/>
          </div>
        </xsl:when>
      </xsl:choose>
    </header>
    <!-- 
      <a href="#" onclick="return butCit(this)" title="ocultar / mostrar las citas" class="but">–/+</a>
    -->
  </xsl:template>
  <!-- Grammatical information, with a comma before (but not a new line, the reason of xsl:text)  -->
  <xsl:template match="tei:form / tei:gramGrp">
    <xsl:text>, </xsl:text>
    <span class="gram">
      <xsl:apply-templates/>
    </span>
  </xsl:template>

  <!-- Headword -->
  <xsl:template match="tei:orth[@type !='latin']">
    <strong class="{@type} grc">
      <xsl:apply-templates/>
    </strong>
  </xsl:template>
  <!-- Other principal headers -->
  <xsl:template match="tei:orth[@type ='lemma'][position() !=1]">
    <strong class="lemma2 grc">
      <xsl:apply-templates/>
    </strong>
  </xsl:template>
  <!-- Latin alolemas in italic not bold -->
  <xsl:template match="tei:orth[@type ='latin']">
    <em class="{@type}">
      <xsl:apply-templates/>
    </em>
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
    <xsl:apply-templates/>
  </xsl:template>
  <xsl:template match="tei:ref">
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
    <dfn>
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
    <span class="{local-name()}">
      <xsl:attribute name="id">
        <xsl:call-template name="id"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </span>
  </xsl:template>
  <!-- Not used for the moment in dge
  <xsl:template match="tei:lbl">
    <xsl:text> </xsl:text>
    <xsl:apply-templates/>
    <xsl:text> </xsl:text>
  </xsl:template>
  -->
  <!-- Greek context quoted -->
  <xsl:template match="tei:quote[@xml:lang='grc']">
    <q class="grc">
      <xsl:attribute name="class">
        <xsl:value-of select="@xml:lang"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </q>
  </xsl:template>
  <!-- usage mark -->
  <xsl:template match="tei:usg">
    <label class="usg">
      <xsl:apply-templates/>
    </label>
  </xsl:template>
  <!-- Translation of greek context quoted -->
  <xsl:template match="tei:quote">
    <em class="tr">
      <xsl:text> </xsl:text>
      <xsl:apply-templates/>
    </em>
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
    <em>
      <xsl:apply-templates/>
    </em>
  </xsl:template>
  <!-- bold -->
  <xsl:template match="tei:hi[substring(@rend, 1, 1)='b']">
    <b>
      <xsl:apply-templates/>
    </b>
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
  <!-- normal in italic, encoded as italic in italic -->
  <xsl:template match="tei:hi[@rend='roman'] | tei:hi[@rend='n']">
    <i>
      <xsl:apply-templates/>
    </i>
  </xsl:template>
  <!-- Different sections with labels: etymologia, morphological informations, Dmic -->
  <xsl:template match="tei:form//tei:form | tei:etym ">
    <xsl:variable name="class">
      <xsl:choose>
        <xsl:when test="@type">
          <xsl:value-of select="translate(@type, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="local-name()"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <div class="{$class}">
      <label>
        <xsl:choose>
          <xsl:when test="self::tei:etym">Etimología</xsl:when>
          <xsl:when test="@type='alolema'">Alolema(s)</xsl:when>
          <xsl:when test="@type='grafia'">Grafía</xsl:when>
          <xsl:when test="@type='prosodia'">Prosodia</xsl:when>
          <xsl:when test="@type='morfologia'">Morfología</xsl:when>
        </xsl:choose>
      </label>
      <xsl:text>: </xsl:text>
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  <xsl:template match="tei:bibl[@type='dmic']">
    <div class="dmic">
      <xsl:apply-templates/>
    </div>    
  </xsl:template>
  <xsl:template match="tei:bibl[@type='dmic']/tei:title">
    <label>
      <xsl:apply-templates/>
    </label>    
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
    -->
    <xsl:variable name="cit">
      <xsl:for-each select="ancestor-or-self::tei:cit[1]">
        <xsl:number level="any" from="tei:entry"/>
      </xsl:for-each>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="@xml:id">
        <xsl:value-of select="@xml:id"/>
      </xsl:when>
      <xsl:when test="$cit != ''">
        <xsl:value-of select="ancestor-or-self::tei:entry[1]/@xml:id"/>
        <xsl:text>_cit</xsl:text>
        <xsl:value-of select="$cit"/>
      </xsl:when>
      <xsl:when test="ancestor-or-self::tei:sense[@n][1]">
        <xsl:value-of select="ancestor-or-self::tei:entry[1]/@xml:id"/>
        <xsl:text>_</xsl:text>
        <xsl:value-of select="ancestor-or-self::tei:sense[@n][1]/@n"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
</xsl:transform>
