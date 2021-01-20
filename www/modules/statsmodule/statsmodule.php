<?php
/**
 * Copyright (C) 2017-2019 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2019 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StatsModule
 *
 * Translations of submodules:
 *
 * // pagesnotfound
 * $this->l('Pages not found');
 * $this->l('Adds a tab to the Stats dashboard, showing the pages requested by your visitors that have not been found.');
 * $this->l('The "pages not found" cache has been emptied.')
 * $this->l('The "pages not found" cache has been deleted.')
 * $this->l('Guide')
 * $this->l('404 errors')
 * $this->l('A 404 error is an HTTP error code which means that the file requested by the user cannot be found. In your case it means that one of your visitors entered a wrong URL in the address bar, or that you or another website has a dead link. When possible, the referrer is shown so you can find the page/site which contains the dead link. If not, it generally means that it is a direct access, so someone may have bookmarked a link which doesn\'t exist anymore.')
 * $this->l('How to catch these errors?')
 * $this->l('If your webhost supports .htaccess files, you can create one in the root directory of PrestaShop and insert the following line inside: "%s".'),
 * $this->l('A user requesting a page which doesn\'t exist will be redirected to the following page: %s. This module logs access to this page.'),
 * $this->l('You must use a .htaccess file to redirect 404 errors to the "404.php" page.')
 * $this->l('Page')
 * $this->l('Referrer')
 * $this->l('Counter')
 * $this->l('No "page not found" issue registered for now.')
 * $this->l('Empty database')
 * $this->l('Empty ALL "pages not found" notices for this period')
 * $this->l('Empty ALL "pages not found" notices')
 *
 * // sekeywords
 * $this->l('Search engine keywords');
 * $this->l('Displays which keywords have led visitors to your website.');
 * $this->l('Guide')
 * $this->l('Identify external search engine keywords')
 * $this->l('This is one of the most common ways of finding a website through a search engine.')
 * $this->l('Identifying the most popular keywords entered by your new visitors allows you to see the products you should put in front if you want to achieve better visibility in search engines.')
 * $this->l('How does it work?')
 * $this->l('When a visitor comes to your website, the web server notes the URL of the site he/she comes from. This module then parses the URL, and if it finds a reference to a known search engine, it finds the keywords in it.')
 * $this->l('This module can recognize all the search engines listed in PrestaShop\'s Stats/Search Engine page -- and you can add more!')
 * $this->l('IMPORTANT NOTE: in September 2013, Google chose to encrypt its searches queries using SSL. This means all the referer-based tools in the World (including this one) cannot identify Google keywords anymore.')
 * $this->l('%d keyword matches your query.')
 * $this->l('%d keywords match your query.')
 * $this->l('Filter by keyword')
 * $this->l('And min occurrences')
 * $this->l('And min occurrences')
 * $this->l('Keywords')
 * $this->l('Occurrences')
 * $this->l('CSV Export')
 * $this->l('No keywords')
 * $this->l('Top 10 keywords')
 * $this->l('Others')
 *
 * // statsbestcategories
 * $this->l('Empty recordset returned');
 * $this->l('Displaying %1$s of %2$s');
 * $this->l('Name')
 * $this->l('Total Quantity Sold')
 * $this->l('Total Price')
 * $this->l('Total Margin')
 * $this->l('Total Viewed')
 * $this->l('Best categories')
 * $this->l('Adds a list of the best categories to the Stats dashboard.')
 * $this->l('CSV Export')
 * $this->l('Display final level categories only (that have no child categories)')
 *
 *  // statsbestcustomers
 * $this->l('Empty recordset returned');
 * $this->l('Displaying %1$s of %2$s');
 * $this->l('Last Name')
 * $this->l('First Name')
 * $this->l('Email')
 * $this->l('Visits')
 * $this->l('Valid orders')
 * $this->l('Money spent')
 * $this->l('Best customers')
 * $this->l('Adds a list of the best customers to the Stats dashboard.')
 * $this->l('Guide')
 * $this->l('Develop clients\' loyalty')
 * $this->l('Keeping a client can be more profitable than gaining a new one. That is one of the many reasons it is necessary to cultivate customer loyalty.')
 * $this->l('Word of mouth is also a means for getting new, satisfied clients. A dissatisfied customer can hurt your e-reputation and obstruct future sales goals.')
 * $this->l('In order to achieve this goal, you can organize:')
 * $this->l('Punctual operations: commercial rewards (personalized special offers, product or service offered), non commercial rewards (priority handling of an order or a product), pecuniary rewards (bonds, discount coupons, payback).')
 * $this->l('Sustainable operations: loyalty points or cards, which not only justify communication between merchant and client, but also offer advantages to clients (private offers, discounts).')
 * $this->l('These operations encourage clients to buy products and visit your online store more regularly.')
 * $this->l('CSV Export')
 *
 * // statsbestmanufacturers
 * $this->l('Empty record set returned')
 * $this->l('Displaying %1$s of %2$s'), '{0} - {1}', '{2}')
 * $this->l('Name')
 * $this->l('Quantity sold')
 * $this->l('Total paid')
 * $this->l('Best manufacturers')
 * $this->l('Adds a list of the best manufacturers to the Stats dashboard.')
 * $this->l('CSV Export')
 *
 * // statsbestproducts
 * $this->l('An empty record-set was returned.');
 * $this->l('Displaying %1$s of %2$s');
 * $this->l('Reference'),
 * $this->l('Name')
 * $this->l('Quantity sold')
 * $this->l('Price sold')
 * $this->l('Sales')
 * $this->l('Quantity sold in a day')
 * $this->l('Page views')
 * $this->l('Available quantity for sale')
 * $this->l('Active')
 * $this->l('Best-selling products')
 * $this->l('Adds a list of the best-selling products to the Stats dashboard.')
 * $this->l('CSV Export')
 *
 * // statsbestsuppliers
 * $this->l('Empty record set returned')
 * $this->l('Displaying %1$s of %2$s'), '{0} - {1}', '{2}')
 * $this->l('Name')
 * $this->l('Quantity sold')
 * $this->l('Total paid')
 * $this->l('Best suppliers')
 * $this->l('Adds a list of the best suppliers to the Stats dashboard.')
 * $this->l('CSV Export')
 *
 * // statsbestvouchers
 * $this->l('Empty recordset returned.')
 * $this->l('Displaying %1$s of %2$s'), '{0} - {1}', '{2}')
 * $this->l('Code')
 * $this->l('Name')
 * $this->l('Sales')
 * $this->l('Total used')
 * $this->l('Best vouchers')
 * $this->l('Adds a list of the best vouchers to the Stats dashboard.')
 * $this->l('CSV Export')
 *
 * // statscarrier
 * $this->l('Carrier distribution')
 * $this->l('Adds a graph displaying each carriers\' distribution to the Stats dashboard.')
 * $this->l('All')
 * $this->l('Filter')
 * $this->l('This graph represents the carrier distribution for your orders. You can also narrow the focus of the graph to display distribution for a particular order status.')
 * $this->l('CSV Export')
 * $this->l('No valid orders have been received for this period.')
 * $this->l('Percentage of orders listed by carrier.')
 * $this->l('Catalog statistics')
 * $this->l('Adds a tab containing general statistics about your catalog to the Stats dashboard.')
 * $this->l('(1 purchase / %d visits)')
 * $this->l('Choose a category')
 * $this->l('All')
 * $this->l('Products available:')
 * $this->l('Average price (base price):')
 * $this->l('Product pages viewed:')
 * $this->l('Products bought:')
 * $this->l('Average number of page visits:')
 * $this->l('Average number of purchases:')
 * $this->l('Images available:')
 * $this->l('Average number of images:')
 * $this->l('Products never viewed:')
 * $this->l('Products never purchased:')
 * $this->l('Conversion rate*:')
 * $this->l('Defines the average conversion rate for the product page. It is possible to purchase a product without viewing the product page, so this rate can be greater than 1.')
 * $this->l('Products never purchased')
 * $this->l('ID')
 * $this->l('Name')
 * $this->l('Edit / View')
 * $this->l('Edit')
 * $this->l('View')
 *
 * // statscheckup
 * $this->l('Catalog evaluation')
 * $this->l('Adds a quick evaluation of your catalog quality to the Stats dashboard.')
 * $this->l('Configuration updated')
 * $this->l('Configuration updated')
 * $this->l('Bad')
 * $this->l('Average')
 * $this->l('Good')
 * $this->l('No product was found.')
 * $this->l('Descriptions'), 'text' => $this->l('chars (without HTML)')
 * $this->l('Images'), 'text' => $this->l('images')
 * $this->l('Sales'), 'text' => $this->l('orders / month')
 * $this->l('Available quantity for sale'), 'text' => $this->l('items')
 * $this->l('Not enough')
 * $this->l('Alright')
 * $this->l('Less than')
 * $this->l('Greater than')
 * $this->l('Save')
 * $this->l('Order by')
 * $this->l('ID')
 * $this->l('Name')
 * $this->l('Sales')
 * $this->l('ID')
 * $this->l('Item')
 * $this->l('Active')
 * $this->l('Desc.')
 * $this->l('Images')
 * $this->l('Sales')
 * $this->l('Available quantity for sale')
 * $this->l('Global')
 * $this->l('Active')
 * $this->l('Desc.')
 * $this->l('Images')
 * $this->l('Sales')
 * $this->l('Available quantity for sale')
 * $this->l('Global')
 *
 * // statsequipment
 * $this->l('Browsers and operating systems')
 * $this->l('Adds a tab containing graphs about web browser and operating system usage to the Stats dashboard.')
 * $this->l('Guide')
 * $this->l('Making sure that your website is accessible to as many people as possible')
 * $this->l('When managing a website, it is important to keep track of the software used by visitors so as to be sure that the site displays the same way for everyone. PrestaShop was built to be compatible with the most recent Web browsers and computer operating systems (OS). However, because you may end up adding advanced features to your website or even modifying the core PrestaShop code, these additions may not be accessible to everyone. That is why it is a good idea to keep track of the percentage of users for each type of software before adding or changing something that only a limited number of users will be able to access.')
 * $this->l('Indicates the percentage of each web browser used by customers.')
 * $this->l('CSV Export')
 * $this->l('Indicates the percentage of each operating system used by customers.')
 * $this->l('CSV Export')
 * $this->l('Plugins')
 * $this->l('Web browser used')
 * $this->l('Operating system used')
 *
 * // statsgroups
 * $this->l('Group');
 * $this->l('Members per group')
 *
 * // statsforecast
 * $this->l('Stats Dashboard')
 * $this->l('This is the main module for the Stats dashboard. It displays a summary of all your current statistics.')
 * $this->l('The listed amounts do not include tax.')
 * $this->l('Time frame')
 * $this->l('Daily')
 * $this->l('Weekly')
 * $this->l('Monthly')
 * $this->l('Yearly')
 * $this->l('Visits')
 * $this->l('Registrations')
 * $this->l('Placed orders')
 * $this->l('Bought items')
 * $this->l('Percentage of registrations')
 * $this->l('Percentage of orders')
 * $this->l('Revenue')
 * $this->l('Total')
 * $this->l('Average')
 * $this->l('Forecast')
 * $this->l('Conversion')
 * $this->l('Visitors')
 * $this->l('Accounts')
 * $this->l('Orders')
 * $this->l('Full carts')
 * $this->l('Registered visitors')
 * $this->l('Orders')
 * $this->l('Visitors')
 * $this->l('Orders')
 * $this->l('Carts')
 * $this->l('A simple statistical calculation lets you know the monetary value of your visitors:')
 * $this->l('On average, each visitor places an order for this amount:')
 * $this->l('On average, each registered visitor places an order for this amount:')
 * $this->l('Payment distribution')
 * $this->l('The amounts include taxes, so you can get an estimation of the commission due to the payment method.')
 * $this->l('Zone:')
 * $this->l('-- No filter --')
 * $this->l('Module')
 * $this->l('Orders')
 * $this->l('Sales')
 * $this->l('Average cart value')
 * $this->l('Category distribution')
 * $this->l('Zone')
 * $this->l('-- No filter --')
 * $this->l('Category')
 * $this->l('Products sold')
 * $this->l('Sales')
 * $this->l('Percentage of products sold')
 * $this->l('Percentage of sales')
 * $this->l('Average price')
 * $this->l('Unknown')
 * $this->l('Language distribution')
 * $this->l('Language')
 * $this->l('Sales')
 * $this->l('Percentage')
 * $this->l('Growth')
 * $this->l('Zone distribution')
 * $this->l('Zone')
 * $this->l('Orders')
 * $this->l('Sales')
 * $this->l('Percentage of orders')
 * $this->l('Percentage of sales')
 * $this->l('Undefined')
 * $this->l('Currency distribution')
 * $this->l('Zone:')
 * $this->l('-- No filter --')
 * $this->l('Currency')
 * $this->l('Orders')
 * $this->l('Sales (converted)')
 * $this->l('Percentage of orders')
 * $this->l('Percentage of sales')
 * $this->l('Attribute distribution')
 * $this->l('Group')
 * $this->l('Attribute')
 * $this->l('Quantity of products sold')
 *
 * // statslive
 * $this->l('Visitors online')
 * $this->l('Adds a list of customers and visitors who are currently online to the Stats dashboard.')
 * $this->l('You must activate the "Save page views for each customer" option in the "Data mining for statistics" (StatsData) module in order to see the pages that your visitors are currently viewing.')
 * $this->l('Current online customers')
 * $this->l('Total:')
 * $this->l('Customer ID')
 * $this->l('Name')
 * $this->l('Current page')
 * $this->l('View customer profile')
 * $this->l('There are no active customers online right now.')
 * $this->l('Current online visitors')
 * $this->l('Total:')
 * $this->l('Guest ID')
 * $this->l('IP')
 * $this->l('Last activity')
 * $this->l('Current page')
 * $this->l('Referrer')
 * $this->l('None')
 * $this->l('Undefined')
 * $this->l('There are no visitors online.')
 * $this->l('Notice')
 * $this->l('Add or remove an IP address.')
 * $this->l('Maintenance IPs are excluded from the online visitors.')
 *
 * // statsnewsletter
 * $this->l('Newsletter')
 * $this->l('Adds a tab with a graph showing newsletter registrations to the Stats dashboard.')
 * $this->l('Customer registrations:')
 * $this->l('Visitor registrations: ')
 * $this->l('Both:')
 * $this->l('CSV Export')
 * $this->l('The "'.$this->newsletter_module_human_readable_name.'" module must be installed.')
 * $this->l('Newsletter statistics')
 * $this->l('customers')
 * $this->l('Visitors')
 * $this->l('Both')
 *
 * // statsordersprofit
 * $this->l('An empty record-set was returned.');
 * $this->l('Displaying %1$s of %2$s');
 * $this->l('Order ID'),
 * $this->l('Invoice Number')
 * $this->l('Invoice Date')
 * $this->l('Paid')
 * $this->l('Total')
 * $this->l('Shipping')
 * $this->l('Tax')
 * $this->l('Cost')
 * $this->l('Discount')
 * $this->l('Profit')
 * $this->l('Orders Profit')
 * $this->l('Adds a list of the orders profit to the Stats dashboard.')
 * $this->l('CSV Export')
 *
 * // statsorigins
 * $this->l('Visitors origin')
 * $this->l('Adds a graph displaying the websites your visitors came from to the Stats dashboard.')
 * $this->l('Origin')
 * $this->l('Direct link')
 * $this->l('In the tab, we break down the 10 most popular referral websites that bring customers to your online store.')
 * $this->l('Guide')
 * $this->l('What is a referral website?')
 * $this->l('The referrer is the URL of the previous webpage from which a link was followed by the visitor.')
 * $this->l('A referrer also enables you to know which keywords visitors use in search engines when browsing for your online store.')
 * $this->l('A referrer can be:')
 * $this->l('Someone who posts a link to your shop.')
 * $this->l('A partner who has agreed to a link exchange in order to attract new customers.')
 * $this->l('CSV Export')
 * $this->l('Origin')
 * $this->l('Total')
 * $this->l('Direct links only')
 * $this->l('Top ten referral websites')
 * $this->l('Others')
 *
 * // statspersonalinfos
 * $this->l('Registered customer information')
 * $this->l('Adds information about your registered customers (such as gender and age) to the Stats dashboard.')
 * $this->l('Guide')
 * $this->l('Target your audience')
 * $this->l('In order for each message to have an impact, you need to know who it is being addressed to. ')
 * $this->l('Defining your target audience is essential when choosing the right tools to win them over.')
 * $this->l('It is best to limit an action to a group -- or to groups -- of clients.')
 * $this->l('Storing registered customer information allows you to accurately define customer profiles so you can adapt your special deals and promotions.')
 * $this->l('You can increase your sales by:')
 * $this->l('Launching targeted advertisement campaigns.')
 * $this->l('Contacting a group of clients by email or newsletter.')
 * $this->l('Gender distribution allows you to determine the percentage of men and women shoppers on your store.')
 * $this->l('CSV Export')
 * $this->l('Age ranges allow you to better understand target demographics.')
 * $this->l('CSV Export')
 * $this->l('Country distribution allows you to analyze which part of the World your customers are shopping from.')
 * $this->l('CSV Export')
 * $this->l('Currency range allows you to determine which currency your customers are using.')
 * $this->l('CSV Export')
 * $this->l('Language distribution allows you to analyze the browsing language used by your customers.')
 * $this->l('CSV Export')
 * $this->l('No customers have registered yet.')
 * $this->l('Gender distribution')
 * $this->l('Male')
 * $this->l('Female')
 * $this->l('Unknown')
 * $this->l('Age range')
 * $this->l('0-18')
 * $this->l('18-24')
 * $this->l('25-34')
 * $this->l('35-49')
 * $this->l('50-59')
 * $this->l('60+')
 * $this->l('Unknown')
 * $this->l('Country distribution')
 * $this->l('Language distribution')
 * $this->l('Currency distribution')
 *
 * // statsproduct
 * $this->l('Product details')
 * $this->l('Adds detailed statistics for each product to the Stats dashboard.')
 * $this->l('Guide')
 * $this->l('Number of purchases compared to number of views')
 * $this->l('After choosing a category and selecting a product, informational graphs will appear.')
 * $this->l('If you notice that a product is often purchased but viewed infrequently, you should display it more prominently in your Front Office.')
 * $this->l('On the other hand, if a product has many views but is not often purchased, we advise you to check or modify this product\'s information, description and photography again, see if you can find something better.')
 * $this->l('Details')
 * $this->l('Total bought')
 * $this->l('Sales (tax excluded)')
 * $this->l('Total viewed')
 * $this->l('Conversion rate')
 * $this->l('CSV Export')
 * $this->l('Attribute sales distribution')
 * $this->l('CSV Export')
 * $this->l('Sales')
 * $this->l('Date')
 * $this->l('Order')
 * $this->l('Customer')
 * $this->l('Attribute')
 * $this->l('Attribute')
 * $this->l('Price')
 * $this->l('Cross selling')
 * $this->l('Product name')
 * $this->l('Average price')
 * $this->l('Quantity sold')
 * $this->l('Click on a product to access its statistics!')
 * $this->l('Choose a category')
 * $this->l('All')
 * $this->l('Products available')
 * $this->l('Reference')
 * $this->l('Name')
 * $this->l('Available quantity for sale')
 * $this->l('CSV Export')
 * $this->l('Popularity')
 * $this->l('Sales')
 * $this->l('Visits (x100)')
 * $this->l('Attributes')
 * $this->l('Reference')
 * $this->l('Name')
 * $this->l('Stock')
 *
 * // statsregistrations
 * $this->l('Customer accounts')
 * $this->l('Adds a registration progress tab to the Stats dashboard.')
 * $this->l('Number of visitors who stopped at the registering step:')
 * $this->l('Number of visitors who placed an order directly after registration:')
 * $this->l('Total customer accounts:')
 * $this->l('Guide')
 * $this->l('Number of customer accounts created')
 * $this->l('The total number of accounts created is not in itself important information. However, it is beneficial to analyze the number created over time. This will indicate whether or not things are on the right track. You feel me?')
 * $this->l('How to act on the registrations\' evolution?')
 * $this->l('If you let your shop run without changing anything, the number of customer registrations should stay stable or show a slight decline.')
 * $this->l('A significant increase or decrease in customer registration shows that there has probably been a change to your shop. With that in mind, we suggest that you identify the cause, correct the issue and get back in the business of making money!')
 * $this->l('Here is a summary of what may affect the creation of customer accounts:')
 * $this->l('An advertising campaign can attract an increased number of visitors to your online store. This will likely be followed by an increase in customer accounts and profit margins, which will depend on customer "quality." Well-targeted advertising is typically more effective than large-scale advertising... and it\'s cheaper too!')
 * $this->l('Specials, sales, promotions and/or contests typically demand a shoppers\' attentions. Offering such things will not only keep your business lively, it will also increase traffic, build customer loyalty and genuinely change your current e-commerce philosophy.')
 * $this->l('Design and user-friendliness are more important than ever in the world of online sales. An ill-chosen or hard-to-follow graphical theme can keep shoppers at bay. This means that you should aspire to find the right balance between beauty and functionality for your online store.')
 * $this->l('CSV Export')
 * $this->l('Number of customer accounts created')
 *
 * // statssales
 * $this->l('Sales and orders')
 * $this->l('Adds graphics presenting the evolution of sales and orders to the Stats dashboard.')
 * $this->l('Guide')
 * $this->l('About order statuses')
 * $this->l('In your Back Office, you can modify the following order statuses: Awaiting Check Payment, Payment Accepted, Preparation in Progress, Shipping, Delivered, Canceled, Refund, Payment Error, Out of Stock, and Awaiting Bank Wire Payment.')
 * $this->l('These order statuses cannot be removed from the Back Office; however you have the option to add more.')
 * $this->l('The following graphs represent the evolution of your shop\'s orders and sales turnover for a selected period.')
 * $this->l('You should often consult this screen, as it allows you to quickly monitor your shop\'s sustainability. It also allows you to monitor multiple time periods.')
 * $this->l('Only valid orders are graphically represented.')
 * $this->l('All countries')
 * $this->l('Filter')
 * $this->l('Orders placed:')
 * $this->l('Products bought:')
 * $this->l('CSV Export')
 * $this->l('Sales:')
 * $this->l('CSV Export')
 * $this->l('You can view the distribution of order statuses below.')
 * $this->l('No orders for this period.')
 * $this->l('CSV Export')
 * $this->l('Orders placed')
 * $this->l('Products bought')
 * $this->l('Products:')
 * $this->l('Percentage of orders per status.')
 * $this->l('Sales currency: %s')
 *
 * // statssearch
 * $this->l('Shop search')
 * $this->l('Adds a tab to the Stats dashboard, showing which keywords have been searched by your store\'s visitors.')
 * $this->l('Keywords')
 * $this->l('Occurrences')
 * $this->l('Results')
 * $this->l('CSV Export')
 * $this->l('Cannot find any keywords that have been searched for more than once.')
 * $this->l('Top 10 keywords')
 * $this->l('Others')
 *
 * // statsstock
 * $this->l('Available quantities')
 * $this->l('Adds a tab showing the quantity of available products for sale to the Stats dashboard.')
 * $this->l('Evaluation of available quantities for sale')
 * $this->l('Category')
 * $this->l('All')
 * $this->l('Your catalog is empty.')
 * $this->l('ID')
 * $this->l('Ref.')
 * $this->l('Item')
 * $this->l('Available quantity for sale')
 * $this->l('Price*')
 * $this->l('Value')
 * $this->l('Total quantities')
 * $this->l('Average price')
 * $this->l('Total value')
 * $this->l('This section corresponds to the default wholesale price according to the default supplier for the product. An average price is used when the product has attributes.')
 *
 * // statsvisits
 * $this->l('Visits and Visitors')
 * $this->l('Adds statistics about your visits and visitors to the Stats dashboard.')
 * $this->l('Guide')
 * $this->l('Determine the interest of a visit')
 * $this->l('The visitors\' evolution graph strongly resembles the visits\' graph, but provides additional information:')
 * $this->l('If this is the case, congratulations, your website is well planned and pleasing. Glad to see that you\'ve been paying attention.')
 * $this->l('Otherwise, the conclusion is not so simple. The problem can be aesthetic or ergonomic. It is also possible that many visitors have mistakenly visited your URL without possessing a particular interest in your shop. This strange and ever-confusing phenomenon is most likely cause by search engines. If this is the case, you should consider revising your SEO structure.')
 * $this->l('This information is mostly qualitative. It is up to you to determine the interest of a disjointed visit.')
 * $this->l('A visit corresponds to an internet user coming to your shop, and until the end of their session, only one visit is counted.')
 * $this->l('A visitor is an unknown person who has not registered or logged into your store. A visitor can also be considered a person who has visited your shop multiple times.')
 * $this->l('Total visits:')
 * $this->l('Total visitors:')
 * $this->l('CSV Export')
 * $this->l('Number of visits and unique visitors')
 * $this->l('Visits')
 * $this->l('Visitors')
 */
class StatsModule extends ModuleStats
{
    private $query;
    private $columns;
    private $default_sort_column;
    private $default_sort_direction;
    private $empty_message;
    private $paging_message;

    public $modules = [
        'pagesnotfound',
        'sekeywords',
        'statsbestcategories',
        'statsbestcustomers',
        'statsbestmanufacturers',
        'statsbestproducts',
        'statsbestsuppliers',
        'statsbestvouchers',
        'statscarrier',
        'statscatalog',
        'statscheckup',
        'statsequipment',
        'statsforecast',
        'statsgroups',
        'statslive',
        'statsnewsletter',
        'statsordersprofit',
        'statsorigin',
        'statspersonalinfos',
        'statsproduct',
        'statsregistrations',
        'statssales',
        'statssearch',
        'statsstock',
        'statsvisits',
    ];

    public function __construct()
    {
        $this->name = 'statsmodule';
        $this->tab = 'analytics_stats';
        $this->version = '2.1.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->default_sort_column = 'totalPriceSold';
        $this->default_sort_direction = 'DESC';
        $this->empty_message = $this->l('Empty recordset returned');
        $this->paging_message = sprintf($this->l('Displaying %1$s of %2$s'), '{0} - {1}', '{2}');

        $this->columns = [
            [
                'id'        => 'name',
                'header'    => $this->l('Name'),
                'dataIndex' => 'name',
                'align'     => 'left',
            ],
            [
                'id'        => 'totalQuantitySold',
                'header'    => $this->l('Total Quantity Sold'),
                'dataIndex' => 'totalQuantitySold',
                'align'     => 'center',
            ],
            [
                'id'        => 'totalPriceSold',
                'header'    => $this->l('Total Price'),
                'dataIndex' => 'totalPriceSold',
                'align'     => 'right',
            ],
            [
                'id'        => 'totalWholeSalePriceSold',
                'header'    => $this->l('Total Margin'),
                'dataIndex' => 'totalWholeSalePriceSold',
                'align'     => 'center',
            ],
            [
                'id'        => 'totalPageViewed',
                'header'    => $this->l('Total Viewed'),
                'dataIndex' => 'totalPageViewed',
                'align'     => 'center',
            ],
        ];

        $this->displayName = $this->l('Statistics Module');
        $this->description = $this->l('Adds several statistics to the shop.');
        $this->tb_versions_compliancy = '> 1.0.3';
        $this->tb_min_version = '1.0.4';
    }

    /**
     * Install this module
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!defined('TB_INSTALLATION_IN_PROGRESS') || !TB_INSTALLATION_IN_PROGRESS) {
            foreach ($this->modules as $moduleCode) {
                $moduleInstance = Module::getInstanceByName($moduleCode);

                if (is_dir(_PS_MODULE_DIR_.$moduleCode)) {
                    try {
                        if (is_object($moduleInstance) && $moduleInstance->uninstall() || !is_object($moduleInstance) || !Module::isInstalled($moduleCode)) {
                            $this->recursiveDeleteOnDisk(_PS_MODULE_DIR_.$moduleCode);
                        }
                    } catch (Exception $e) {
                        // Let it fail, time to go on
                    }
                }
            }
        }

        if (!parent::install()) {
            return false;
        }

        $this->registerHook('search');
        $this->registerHook('top');
        $this->registerHook('AdminStatsModules');

        if (!defined('TB_INSTALLATION_IN_PROGRESS') || !TB_INSTALLATION_IN_PROGRESS) {
            $this->unregisterStatsModuleHooks();
        }


        // statscheckup
        $confs = [
            'CHECKUP_DESCRIPTIONS_LT' => 100,
            'CHECKUP_DESCRIPTIONS_GT' => 400,
            'CHECKUP_IMAGES_LT'       => 1,
            'CHECKUP_IMAGES_GT'       => 2,
            'CHECKUP_SALES_LT'        => 1,
            'CHECKUP_SALES_GT'        => 2,
            'CHECKUP_STOCK_LT'        => 1,
            'CHECKUP_STOCK_GT'        => 3,
        ];
        foreach ($confs as $confname => $confdefault) {
            if (!Configuration::get($confname)) {
                Configuration::updateValue($confname, (int) $confdefault);
            }
        }

        // Search Engine Keywords
        Configuration::updateValue('SEK_MIN_OCCURENCES', 1);
        Configuration::updateValue('SEK_FILTER_KW', '');

        Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sekeyword` (
			id_sekeyword INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			keyword VARCHAR(256) NOT NULL,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_sekeyword)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pagenotfound` (
			id_pagenotfound INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			request_uri VARCHAR(256) NOT NULL,
			http_referer VARCHAR(256) NOT NULL,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_pagenotfound),
			INDEX (`date_add`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );

        Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'statssearch` (
			id_statssearch INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
		  	id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			keywords VARCHAR(255) NOT NULL,
			results INT(6) NOT NULL DEFAULT 0,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_statssearch)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        return true;
    }


    private function recursiveDeleteOnDisk($dir)
    {
        if (strpos(realpath($dir), realpath(_PS_MODULE_DIR_)) === false) {
            return;
        }
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') {
                        $this->recursiveDeleteOnDisk($dir.'/'.$object);
                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }


    public function getStatsModulesList()
    {
        foreach ($this->modules as $module) {
            $list[] = ['name' => $module];
        }

        return $list;
    }

    public function executeStatsInstance($moduleName, $hook = false)
    {
        require_once(dirname(__FILE__).'/stats/'.$moduleName.'.php');
        $module = new $moduleName();
        if ($hook) {
            return $module->hookAdminStatsModules(null);
        } else {
            return $module;
        }
    }


    protected function engine($type, $params)
    {
        return call_user_func_array([$this, 'engine'.$type], [$params]);
    }

    protected function getData($layers)
    {
        $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $dateBetween = $this->getDate();
        $idLang = $this->getLang();

        //If column 'order_detail.original_wholesale_price' does not exist, create it
        Db::getInstance(_PS_USE_SQL_SLAVE_)->query('SHOW COLUMNS FROM `'._DB_PREFIX_.'order_detail` LIKE "original_wholesale_price"');
        if (Db::getInstance()->NumRows() == 0) {
            Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'order_detail` ADD `original_wholesale_price` DECIMAL( 20, 6 ) NOT NULL DEFAULT  "0.000000"');
        }

        // If a shop is selected, get all children categories for the shop
        $categories = [];
        if (Shop::getContext() != Shop::CONTEXT_ALL) {
            $sql = 'SELECT c.nleft, c.nright
					FROM '._DB_PREFIX_.'category c
					WHERE c.id_category IN (
						SELECT s.id_category
						FROM '._DB_PREFIX_.'shop s
						WHERE s.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')
					)';
            if ($result = Db::getInstance()->executeS($sql)) {
                $ntree_restriction = array();
                foreach ($result as $row) {
                    $ntree_restriction[] = '(nleft >= '.$row['nleft'].' AND nright <= '.$row['nright'].')';
                }

                if ($ntree_restriction) {
                    $sql = 'SELECT id_category
							FROM '._DB_PREFIX_.'category
							WHERE '.implode(' OR ', $ntree_restriction);
                    if ($result = Db::getInstance()->executeS($sql)) {
                        foreach ($result as $row) {
                            $categories[] = $row['id_category'];
                        }
                    }
                }
            }
        }

        $onlyChildren = '';
        if ((int) Tools::getValue('onlyChildren') == 1) {
            $onlyChildren = 'AND NOT EXISTS (SELECT NULL FROM '._DB_PREFIX_.'category WHERE id_parent = ca.id_category)';
        }

        // Get best categories
        $this->query = '
				SELECT SQL_CALC_FOUND_ROWS ca.`id_category`, CONCAT(parent.name, \' > \', calang.`name`) AS name,
				IFNULL(SUM(t.`totalQuantitySold`), 0) AS totalQuantitySold,
				ROUND(IFNULL(SUM(t.`totalPriceSold`), 0), 2) AS totalPriceSold,
				ROUND(IFNULL(SUM(t.`totalWholeSalePriceSold`), 0), 2) AS totalWholeSalePriceSold,
				(
					SELECT IFNULL(SUM(pv.`counter`), 0)
					FROM `'._DB_PREFIX_.'page` p
					LEFT JOIN `'._DB_PREFIX_.'page_viewed` pv ON p.`id_page` = pv.`id_page`
					LEFT JOIN `'._DB_PREFIX_.'date_range` dr ON pv.`id_date_range` = dr.`id_date_range`
					LEFT JOIN `'._DB_PREFIX_.'product` pr ON CAST(p.`id_object` AS UNSIGNED INTEGER) = pr.`id_product`
					LEFT JOIN `'._DB_PREFIX_.'category_product` capr2 ON capr2.`id_product` = pr.`id_product`
					WHERE capr.`id_category` = capr2.`id_category`
					AND p.`id_page_type` = 1
					AND dr.`time_start` BETWEEN '.$dateBetween.'
					AND dr.`time_end` BETWEEN '.$dateBetween.'
				) AS totalPageViewed,
				(
                    SELECT COUNT(id_category) FROM '._DB_PREFIX_.'category WHERE `id_parent` = ca.`id_category`
			    ) AS hasChildren
			FROM `'._DB_PREFIX_.'category` ca
			LEFT JOIN `'._DB_PREFIX_.'category_lang` calang ON (ca.`id_category` = calang.`id_category` AND calang.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('calang').')
			LEFT JOIN `'._DB_PREFIX_.'category_lang` parent ON (ca.`id_parent` = parent.`id_category` AND parent.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('parent').')
			LEFT JOIN `'._DB_PREFIX_.'category_product` capr ON ca.`id_category` = capr.`id_category`
			LEFT JOIN (
				SELECT pr.`id_product`, t.`totalQuantitySold`, t.`totalPriceSold`, t.`totalWholeSalePriceSold`
				FROM `'._DB_PREFIX_.'product` pr
				LEFT JOIN (
					SELECT pr.`id_product`, pa.`wholesale_price`,
						IFNULL(SUM(cp.`product_quantity`), 0) AS totalQuantitySold,
						IFNULL(SUM(cp.`product_price` * cp.`product_quantity`), 0) / o.conversion_rate AS totalPriceSold,
						IFNULL(SUM(
							CASE
								WHEN cp.`original_wholesale_price` <> "0.000000"
								THEN cp.`original_wholesale_price` * cp.`product_quantity`
								WHEN pa.`wholesale_price` <> "0.000000"
								THEN pa.`wholesale_price` * cp.`product_quantity`
								WHEN pr.`wholesale_price` <> "0.000000"
								THEN pr.`wholesale_price` * cp.`product_quantity`
							END
						), 0) / o.conversion_rate AS totalWholeSalePriceSold
					FROM `'._DB_PREFIX_.'product` pr
					LEFT OUTER JOIN `'._DB_PREFIX_.'order_detail` cp ON pr.`id_product` = cp.`product_id`
					LEFT JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = cp.`id_order`
					LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON pa.`id_product_attribute` = cp.`product_attribute_id`
					'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
					WHERE o.valid = 1
					AND o.invoice_date BETWEEN '.$dateBetween.'
					GROUP BY pr.`id_product`
				) t ON t.`id_product` = pr.`id_product`
			) t	ON t.`id_product` = capr.`id_product`
			'.(($categories) ? 'WHERE ca.id_category IN ('.implode(', ', $categories).')' : '').'
			'.$onlyChildren.'
			GROUP BY ca.`id_category`
			HAVING ca.`id_category` != 1';

        if (Validate::IsName($this->_sort)) {
            $this->query .= ' ORDER BY `'.bqSQL($this->_sort).'`';
            if (isset($this->_direction) && Validate::isSortDirection($this->_direction)) {
                $this->query .= ' '.$this->_direction;
            }
        }

        if (($this->_start === 0 || Validate::IsUnsignedInt($this->_start)) && Validate::IsUnsignedInt($this->_limit)) {
            $this->query .= ' LIMIT '.(int) $this->_start.', '.(int) $this->_limit;
        }

        $values = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query);
        foreach ($values as &$value) {
            if ((int) Tools::getIsset('export') == false) {
                $parts = explode('>', $value['name']);
                $value['name'] = '<i class="icon-folder-open"></i> '.trim($parts[0]).' > ';
                if ((int) $value['hasChildren'] == 0) {
                    $value['name'] .= '&bull; ';
                } else {
                    $value['name'] .= '<i class="icon-folder-open"></i> ';
                }
                $value['name'] .= trim($parts[1]);
            }

            if (isset($value['totalWholeSalePriceSold'])) {
                $value['totalWholeSalePriceSold'] = Tools::displayPrice($value['totalPriceSold'] - $value['totalWholeSalePriceSold'], $currency);
            }
            $value['totalPriceSold'] = Tools::displayPrice($value['totalPriceSold'], $currency);
        }

        $this->_values = $values;
        $this->_totalCount = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()');
    }

    public function render()
    {
        $this->_render->render();
    }

    public function hookSearch($params)
    {
        $sql = 'INSERT INTO `'._DB_PREFIX_.'statssearch` (`id_shop`, `id_shop_group`, `keywords`, `results`, `date_add`)
				VALUES ('.(int) $this->context->shop->id.', '.(int) $this->context->shop->id_shop_group.', \''.pSQL($params['expr']).'\', '.(int) $params['total'].', NOW())';
        Db::getInstance()->execute($sql);
    }

    /**
     * @param array $params Module params
     *
     * @return string
     */
    public function hookTop($params)
    {
        foreach ($this->modules as $moduleName) {
            if (include_once dirname(__FILE__).'/stats/'.$moduleName.'.php') {
                $module = new $moduleName();
                $refl = new ReflectionClass($moduleName);

                if ($refl->getMethod('hookTop')->class != 'StatsModule') {
                    return $module->hookTop($params);
                }
            }
        }

        return '';
    }

    /**
     * Unregister module from hook
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function unregisterStatsModuleHooks()
    {
        // Get hook id if a name is given as argument
        $hookName = 'displayAdminStatsModules';
        $hookId = Hook::getIdByName($hookName);

        $result = true;
        foreach ($this->modules as $moduleName) {
            Hook::exec('actionModuleUnRegisterHookBefore', ['object' => $this, 'hook_name' => $hookName]);

            // Unregister module on hook by id
            $result = Db::getInstance()->delete(
                'hook_module',
                '`id_module` = '.(int) Module::getModuleIdByName($moduleName).' AND `id_hook` = '.(int) $hookId
            ) && $result;

            // Clean modules position
            $this->cleanPositions($hookId);

            Hook::exec('actionModuleUnRegisterHookAfter', ['object' => $this, 'hook_name' => $hookName]);
        }

        return $result;
    }

    protected function csvExport($datas)
    {
        return $this->{"csvExport{$this->type}"}($datas);
    }
}
