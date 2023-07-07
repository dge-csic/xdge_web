<?xml version="1.0" encoding="UTF-8"?>
<!--
<h1XDGE, an XSL parser to populate an sqlite base</h1>

<ul>
  <li>[FG] <a onmouseover="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'fictif.org'">Frédéric Glorieux</a></li>
  <li>[ST] Sabine Thuillier</li>
</ul>


-->
<xsl:transform version="1.1"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="tei"

  xmlns:exslt="http://exslt.org/common"
  xmlns:php="http://php.net/xsl"
  extension-element-prefixes="exslt php"
>
  <!-- screen view of articles -->
  <xsl:import href="xdge_html.xsl"/>
  <!-- used by a test for target of links -->
  <xsl:param name="this">xdge_sql.xsl</xsl:param>
  <!-- keep it, to avoid entities for greek letters in attributes with xsltproc -->
  <xsl:output encoding="UTF-8" indent="yes" method="xml"/>
  <!-- Without root element the process is very long and copy each xmlns on every element -->
  <xsl:template match="/">
    <pre style="font-family:serif;">
      <xsl:apply-templates select="/tei:TEI/tei:text/tei:body/tei:entry" mode="sql"/>
    </pre>
  </xsl:template>
  <!-- Process doc for column data, tei:form/tei:orth[@type='lemma'] -->
  <xsl:template match="tei:entry" mode="sql">
    <xsl:variable name="html">
      <xsl:apply-templates select="."/>
    </xsl:variable>
    <xsl:variable name="lemma">
      <xsl:apply-templates select="tei:form[1]/tei:orth[@type='lemma'][1]" mode="txt"/>
    </xsl:variable>
    <!-- need to be one element to be transformed as a document by exslt:node-set() and passed to php  -->
    <xsl:variable name="label">
      <a>
        <xsl:apply-templates select="tei:form[1]/tei:orth[@type='lemma'][1]/node()"/>
      </a>
    </xsl:variable>
    <xsl:variable name="txt">
      <div>
        <xsl:apply-templates mode="txt"/>
      </div>
    </xsl:variable>
    <xsl:copy-of select="$txt"/>
    <xsl:if test="function-available('php:function')">
      <xsl:variable name="entry" select="php:function('xdge::entry', string(@xml:id), string($lemma),  exslt:node-set($label) , exslt:node-set($html), exslt:node-set($txt))"/>
    </xsl:if>
    <!-- for now, no subpart indexed 
    <xsl:apply-templates mode="sql"/>
    -->
  </xsl:template>
  <!-- Go throw -->
  <xsl:template match="tei:body | tei:text | tei:sense | tei:cit" mode="sql">
    <xsl:apply-templates select="*" mode="sql"/>
  </xsl:template>
  <!-- Stop it -->
  <xsl:template match="tei:teiHeader | tei:form | tei:xr | tei:bibl | tei:num" mode="sql"/>
  <!-- A text view for indexation and snippets, keeping simple typo tags -->
  <!-- For inline, default as html -->
  <xsl:template match="tei:* " mode="txt">
    <xsl:apply-templates select="."/>
  </xsl:template>
  <xsl:template match="tei:cit | tei:dictScrap | tei:entry | tei:etym | tei:foreign | tei:form/tei:form | tei:gramGrp | tei:quote | tei:sense" mode="txt">
    <xsl:apply-templates mode="txt"/>
  </xsl:template>
  <xsl:template match="tei:quote" mode="txt">
    <xsl:if test="position() &gt; 1">
      <xsl:if test="count(preceding-sibling::node()[1]|preceding-sibling::*[1]) = 1">
        <xsl:text> </xsl:text>
      </xsl:if>
    </xsl:if>
    <xsl:apply-templates mode="txt"/>
  </xsl:template>
  <xsl:template match="tei:num" mode="txt">
    <xsl:text> </xsl:text>
    <xsl:choose>
      <xsl:when test=". = '0'"/>
      <xsl:when test=". = ';'">
        <xsl:text> ‒ </xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <strong>
          <xsl:apply-templates/>
          <xsl:text>) </xsl:text>
        </strong>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="tei:form" mode="txt">
    <xsl:for-each select="*">
      <xsl:if test="position() &gt; 1">
        <xsl:text> </xsl:text>
      </xsl:if>
      <xsl:apply-templates select="." mode="txt"/>
    </xsl:for-each>
  </xsl:template>
  <xsl:template match="tei:orth" mode="txt">
    <strong>
      <xsl:apply-templates/>
    </strong>
  </xsl:template>
  <xsl:template match="tei:orth/tei:hi[@rend='sup']" mode="txt"/>
</xsl:transform>
