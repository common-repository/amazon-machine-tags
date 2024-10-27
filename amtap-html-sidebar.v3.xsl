<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
	exclude-result-prefixes="xsl aws xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:aws="http://webservices.amazon.com/AWSECommerceService/2009-07-01"
	xmlns:xhtml="http://www.w3.org/1999/xhtml">
	<xsl:output method="xml"
		media-type="application/xhtml+xml"
		encoding="UTF-8"
		omit-xml-declaration="yes"/>
	
	<!-- Matching the XML root element -->
	<xsl:template match="/">
		<xsl:if test="count(.//aws:Item) &gt; 0">
			<li id="amtap">
				<!-- Headline -->
				<h2><span><xsl:value-of select="//aws:Argument[ @Name = 'AMTAPHeadline' ]/@Value" /></span></h2>
				<!-- Unordered list -->
				<ul>
					<!-- Call next item in the order the request arguments were submitted -->
					<xsl:call-template name="nextItem">
						<xsl:with-param name="itemIds" select="concat(//aws:Argument[ @Name = 'ItemId' ]/@Value, ',')"/>
						<xsl:with-param name="separator">,</xsl:with-param>
					</xsl:call-template>
				</ul>
			</li>
		</xsl:if>
	</xsl:template>

	<!-- List items -->
	<xsl:template name="nextItem">
		<xsl:param name="itemIds"/>
		<xsl:param name="separator"/>
		<xsl:variable name="itemId" select=" substring-before( $itemIds, $separator ) "/>

		<xsl:apply-templates select=" //aws:Item[ aws:ASIN=$itemId ] "/>

		<!-- Call next item in the order the request arguments were submitted -->
		<xsl:if test=" string-length( substring-after( $itemIds, $separator ) ) &gt; 0 ">
			<xsl:call-template name="nextItem">
				<xsl:with-param name="itemIds" select=" substring-after( $itemIds, $separator ) "/>
				<xsl:with-param name="separator" select=" $separator "/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<xsl:template match="aws:Item">
		<li>
			<h3>
				<!-- Item title -->
				<xsl:element name="a">
					<xsl:attribute name="href"><xsl:value-of select="aws:DetailPageURL"/></xsl:attribute>
					<xsl:if test="//aws:Argument[ @Name = 'AMTAPTarget' ]" >
						<xsl:attribute name="target">_blank</xsl:attribute>
					</xsl:if>
					<xsl:choose>
						<!--
							<xsl:when test="contains( aws:ItemAttributes/aws:Title, '.' ) and not( contains( aws:ItemAttributes/aws:Title, '.com' ) )">
							<xsl:value-of select="substring-before( aws:ItemAttributes/aws:Title, '.' )"/>
						</xsl:when>
						-->
						<xsl:when test="contains( aws:ItemAttributes/aws:Title, ':' )">
							<!-- Cut title before first colon -->
							<xsl:value-of select="substring-before( aws:ItemAttributes/aws:Title, ':' )"/>
						</xsl:when>
						<xsl:otherwise>
							<!-- Display full title -->
							<xsl:value-of select="aws:ItemAttributes/aws:Title"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:element>
			</h3>
			<xsl:if test=".//aws:TinyImage">
				<!-- Item image -->
				<xsl:element name="a">
					<xsl:attribute name="href"><xsl:value-of select="aws:DetailPageURL"/></xsl:attribute>
					<xsl:if test="//aws:Argument[ @Name = 'AMTAPTarget' ]" >
						<xsl:attribute name="target">_blank</xsl:attribute>
					</xsl:if>
					<xsl:element name="img">
						<xsl:attribute name="src"><xsl:value-of select=".//aws:TinyImage/aws:URL"/></xsl:attribute>
						<xsl:attribute name="width"><xsl:value-of select=".//aws:TinyImage/aws:Width"/></xsl:attribute>
						<xsl:attribute name="height"><xsl:value-of select=".//aws:TinyImage/aws:Height"/></xsl:attribute>
						<xsl:attribute name="alt"/>
					</xsl:element>
				</xsl:element>
			</xsl:if>
			<p class="author">
				<xsl:choose>
					<xsl:when test="aws:ItemAttributes/aws:Creator">
						<xsl:value-of select="aws:ItemAttributes/aws:Creator"/> (<xsl:value-of select="aws:ItemAttributes/aws:Creator/@Role"/>).
					</xsl:when>
					<xsl:when test="aws:ItemAttributes/aws:Author">
						<xsl:value-of select="aws:ItemAttributes/aws:Author"/>.
					</xsl:when>
					<xsl:when test="aws:ItemAttributes/aws:Artist">
						<xsl:value-of select="aws:ItemAttributes/aws:Artist"/>.
					</xsl:when>
				</xsl:choose>
				<!-- Publisher -->
				<xsl:if test="aws:ItemAttributes/aws:Publisher">
					<xsl:value-of select="aws:ItemAttributes/aws:Publisher"/>
					<xsl:choose>
						<xsl:when test="aws:ItemAttributes/aws:PublicationDate or aws:ItemAttributes/aws:ReleaseDate or aws:ItemAttributes/aws:TheatricalReleaseDate">
							<xsl:text> </xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>, </xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>
				<!-- Publication year -->
				<xsl:choose>
					<xsl:when test="aws:ItemAttributes/aws:PublicationDate">
						<xsl:value-of select="substring(aws:ItemAttributes/aws:PublicationDate, 1, 4)"/>, 
					</xsl:when>
					<xsl:otherwise>
						<xsl:choose>
							<xsl:when test="aws:ItemAttributes/aws:TheatricalReleaseDate">
								<xsl:value-of select="substring(aws:ItemAttributes/aws:TheatricalReleaseDate, 1, 4)"/>, 
							</xsl:when>
							<xsl:otherwise>
								<xsl:if test="aws:ItemAttributes/aws:ReleaseDate">
									<xsl:value-of select="substring(aws:ItemAttributes/aws:ReleaseDate, 1, 4)"/>, 
								</xsl:if>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
				<!-- Binding -->
				<xsl:if test="aws:ItemAttributes/aws:Binding">
					<xsl:value-of select="aws:ItemAttributes/aws:Binding"/>,
				</xsl:if>
				<!-- # of pages -->
				<xsl:if test="aws:ItemAttributes/aws:NumberOfPages">
					<xsl:value-of select="aws:ItemAttributes/aws:NumberOfPages"/> pages,
				</xsl:if>

				<!-- Price -->
				<xsl:variable name="price">
					<xsl:choose>
						<!-- Lowest new price -->
						<xsl:when test="aws:OfferSummary/aws:LowestNewPrice">
							<xsl:value-of select="aws:OfferSummary/aws:LowestNewPrice/aws:FormattedPrice"/>
						</xsl:when>
						<!-- List price -->
						<xsl:when test="aws:ItemAttributes/aws:ListPrice">
							<xsl:value-of select="aws:ItemAttributes/aws:ListPrice/aws:FormattedPrice"/>
						</xsl:when>
						<xsl:when test="aws:Offers/aws:Offer/aws:OfferListing/aws:Price">
							<xsl:value-of select="aws:Offers/aws:Offer[position() = 1]/aws:OfferListing/aws:Price/aws:FormattedPrice"/>
						</xsl:when>
						<!-- Used price -->
						<xsl:otherwise>
							<xsl:if test="aws:OfferSummary/aws:LowestUsedPrice">
								<xsl:value-of select="aws:OfferSummary/aws:LowestUsedPrice/aws:FormattedPrice"/>
							</xsl:if>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<!-- Replace comma with point -->
				<!-- <xsl:value-of select="translate( $price, ',', '.' )"/> -->
				<xsl:value-of select=" $price "/>
			</p>
			<!-- Show star rating -->
			<xsl:if test="( //aws:Argument[ @Name = 'AMTAPRating' ] ) and ( aws:CustomerReviews/aws:AverageRating )" >
				<p class="rating">
					<xsl:variable name="rating">
						<xsl:value-of select="aws:CustomerReviews/aws:AverageRating"/>
					</xsl:variable>
					<xsl:element name="img">
						<xsl:attribute name="width">64</xsl:attribute>
						<xsl:attribute name="height">12</xsl:attribute>
						<xsl:attribute name="alt"><xsl:value-of select=" $rating "/></xsl:attribute>
						<xsl:attribute name="src"><xsl:value-of select=" concat( 'http://g-images.amazon.com/images/G/01/x-locale/common/customer-reviews/stars-', substring-before( $rating, '.' ), '-', substring-after( $rating, '.' ), '.gif' )"/></xsl:attribute>
					</xsl:element>
				</p>
			</xsl:if>
		</li>
	</xsl:template>
</xsl:stylesheet>