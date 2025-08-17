/**
 * Utility functions for link handling and SEO optimization
 */

/**
 * Check if a URL is external (different domain)
 */
export function isExternalUrl(url: string): boolean {
  if (!url) return false
  
  try {
    const urlObj = new URL(url, window.location.origin)
    return urlObj.hostname !== window.location.hostname
  } catch {
    // If URL parsing fails, assume it's external if it starts with http/https
    return url.startsWith('http://') || url.startsWith('https://')
  }
}

/**
 * Get the domain from a URL
 */
export function getDomainFromUrl(url: string): string {
  if (!url) return ''
  
  try {
    const urlObj = new URL(url, window.location.origin)
    return urlObj.hostname
  } catch {
    // Fallback: extract domain from URL string
    const match = url.match(/^(?:https?:\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:/\n?]+)/im)
    return match ? match[1] : ''
  }
}

/**
 * Generate SEO-friendly attributes for external links
 */
export function getExternalLinkAttributes(url: string, customAriaLabel?: string, referrerParam?: string) {
  const isExternal = isExternalUrl(url)
  const domain = getDomainFromUrl(url)
  
  // Add referrer parameter to external URLs
  let finalUrl = url
  if (isExternal && referrerParam) {
    const urlObj = new URL(url, window.location.origin)
    urlObj.searchParams.set('ref', referrerParam)
    finalUrl = urlObj.toString()
  }
  
  const attributes: Record<string, string> = {
    href: finalUrl,
  }
  
  if (isExternal) {
    attributes.target = '_blank'
    attributes.rel = 'noopener noreferrer'
    
    // Generate aria-label for accessibility
    if (customAriaLabel) {
      attributes['aria-label'] = `${customAriaLabel} (opens in new tab)`
    } else if (domain) {
      attributes['aria-label'] = `Visit ${domain} (opens in new tab)`
    } else {
      attributes['aria-label'] = 'External link (opens in new tab)'
    }
  }
  
  return attributes
}

/**
 * Generate meta description for external links
 */
export function generateLinkMetaDescription(url: string, title?: string): string {
  const domain = getDomainFromUrl(url)
  if (title && domain) {
    return `Visit ${title} at ${domain} - External link`
  } else if (domain) {
    return `Visit ${domain} - External link`
  }
  return 'External link'
}

/**
 * Generate referrer parameter for external links
 */
export function generateReferrerParam(source?: string, campaign?: string): string {
  const base = 'uptimekita'
  const parts = [base]
  
  if (source) {
    parts.push(source)
  }
  
  if (campaign) {
    parts.push(campaign)
  }
  
  return parts.join('_')
}

/**
 * Get current page referrer parameter
 */
export function getCurrentPageReferrerParam(): string {
  const path = window.location.pathname
  const page = path.split('/').filter(Boolean).pop() || 'home'
  return generateReferrerParam(page)
}
