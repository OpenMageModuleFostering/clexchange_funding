<?php
// following code snipet will create table `clx_loan_application_detail` and `clx_loan_offer_detail`
$installer = $this;
  
$installer->startSetup();
  
$installer->run("
  
-- DROP TABLE IF EXISTS {$this->getTable('clx_loan_offer_detail')};DROP TABLE IF EXISTS {$this->getTable('clx_loan_application_detail')};
CREATE TABLE {$this->getTable('clx_loan_application_detail')} (
  `clx_loan_application_detail_id` int(11) unsigned NOT NULL auto_increment,
  `application_id` varchar(255) NOT NULL COMMENT 'Application ID',
  `quote_id` varchar(255) NOT NULL COMMENT 'Quote ID',
  `order_id` varchar(255) NOT NULL COMMENT 'Order Number',
  `status` varchar(255) NOT NULL COMMENT 'Clx Status',
  `approvalRequired_flag` boolean NOT NULL default false COMMENT 'Loan Application Flag',
  `mail_sent` boolean NOT NULL default false COMMENT 'Loan Offer Mail Sent',
  `offer_mail_sent_time` datetime NULL COMMENT 'Loan Offer Mail Sent Date',
  `prev_offer_mail_sent_time` datetime NULL COMMENT 'Previous Loan Offer Mail Sent Date',
  `firstName` varchar(255) NOT NULL COMMENT 'Borrowers First Name',
  `lastName` varchar(255) NOT NULL COMMENT 'Borrowers Last Name',
  `emailId` varchar(255) NOT NULL COMMENT 'Borrowers Email Id',
  `birthDate` date NOT NULL COMMENT 'Borrowers Birth Date',
  `mobilePhoneAreaCode` varchar(3) NOT NULL,
  `mobileNumber` varchar(10) NOT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `zipcode` varchar(8) NOT NULL,
  `country` varchar(255) NOT NULL,
  `yearlyIncome` DECIMAL NOT NULL COMMENT 'Borrowers yearly income',
  `employmentStatus` varchar(30) NOT NULL,
  `employerName` varchar(100) NOT NULL,
  `loanTerms` DOUBLE NOT NULL COMMENT 'loan terms in months',
  `employmentStartDate` date NOT NULL,
  `occupation` varchar(100) NOT NULL,
  `bankName` varchar(255) NOT NULL,
  `firstAccountHolderName` varchar(255) NOT NULL,
  `bankAccountType` varchar(100) NOT NULL,
  `bankAccountNumber` varchar(30) NOT NULL,
  `routingNumber` varchar(255) NOT NULL,
  `loanAmount` DECIMAL NOT NULL,
  `loanPurpose` varchar(255) NOT NULL,
  `ssn` varchar(15) NOT NULL,
  `selfReportedCreditScore` int(11) NOT NULL,
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`clx_loan_application_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('clx_loan_offer_detail')};
CREATE TABLE {$this->getTable('clx_loan_offer_detail')} (
  `clx_loan_offer_detail_id` int(11) unsigned NOT NULL auto_increment,
  `loan_application_id` int(11) unsigned NOT NULL COMMENT 'clx_loan_application_detail_id',
  `loanTerm` DECIMAL NOT NULL COMMENT 'Loan Terms',
  `loanAPR` DECIMAL NOT NULL COMMENT 'Loan APR',
  `loanRate` DECIMAL NOT NULL COMMENT 'Loan Rate',
  `paymentFrequency` DECIMAL NOT NULL COMMENT 'Payment Frequency',
  `paymentAmount` DECIMAL NOT NULL COMMENT 'Payment Amount',
  `downPayment` DECIMAL NOT NULL COMMENT 'Down Payment',
  `offerId` varchar(255) NOT NULL COMMENT 'Loan Offer Id',
  `showSelectedOfferUrl` varchar(255) NOT NULL COMMENT 'Show Selected Offer Url',
  `lenderName` varchar(100) NULL COMMENT 'Lender Name',
  PRIMARY KEY (`clx_loan_offer_detail_id`),
  KEY `FK_NEW` (`loan_application_id`),
  CONSTRAINT `FK_NEW` FOREIGN KEY (`loan_application_id`) REFERENCES `{$installer->getTable('clx_loan_application_detail')}` (`clx_loan_application_detail_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  
    ");  
$installer->endSetup(); 
