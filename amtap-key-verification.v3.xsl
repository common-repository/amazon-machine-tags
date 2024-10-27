<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:aws="http://webservices.amazon.com/AWSECommerceService/2009-07-01">
	<xsl:output method="text"/>
	<xsl:template match="/">
		<xsl:apply-templates select=".//aws:IsValid" />
	</xsl:template>

	<!-- valid response -->
	<xsl:template match="aws:IsValid">
		<xsl:if test="contains( ., 'True' )">
			<xsl:text>true</xsl:text>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>