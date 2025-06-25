import React from 'react';
import { Coffee, Heart, Target } from 'lucide-react';

const DonationInfomation = () => {
  // Configuration - Update these with your actual Buy Me a Coffee details
  const config = {
    buyMeACoffeeUrl: 'https://coff.ee/mikebeplusu', // Replace with your actual URL
    pluginName: 'WP Backup',
    developerName: '@Mike', // Replace with your name
  };

  const handleDonateClick = () => {
    window.open(config.buyMeACoffeeUrl, '_blank', 'noopener,noreferrer');
  };

  // convert items of What Your Coffee Money Does to data
  const whatYourCoffeeMoneyDoes = [
    {
      icon: '🚀',
      title: 'Speed Demon Mode',
      description: 'Your coffee fuels lightning-fast updates & new features! ⚡',
    },
    {
      icon: '🛡️',
      title: 'Security-First',
      description: 'Every function built with enterprise-grade security as the top priority! 🔐',
    },
    {
      icon: '💎',
      title: 'Forever Free Gang',
      description: 'This plugin stays 100% free forever - no hidden fees! ✨',
    },
    {
      icon: '🎨',
      title: 'UI/UX Glow Up',
      description: 'Making the interface so smooth it feels like butter! 🧈',
    },
    {
      icon: '🤝',
      title: 'Community Built',
      description: 'Built by the community, for the community - that\'s the vibe! 💪',
    },
  ];

  return (
    <div className="tw-max-w-3xl tw-mx-auto tw-px-2 sm:tw-px-0 tw-py-10 tw-bg-white">
      {/* Fun Header */}
      <div className="tw-text-center tw-mb-8">
        <h2 className="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-4">
          Loving {config.pluginName}? 🚀
        </h2>
        <p className="tw-text-gray-600 tw-leading-relaxed">
          This plugin is totally free forever! ✨ If it's making your life easier, 
          maybe throw me a coffee? ☕ No pressure tho! 😊
        </p>
      </div>

      {/* Fun Stats */}
      <div className="tw-grid tw-grid-cols-3 tw-gap-4 tw-mb-8">
        <div className="tw-text-center tw-p-4 tw-bg-gray-50 tw-rounded-lg">
          <div className="tw-text-xl tw-font-bold tw-text-gray-900">10K+</div>
          <div className="tw-text-sm tw-text-gray-600">Happy Users 😍</div>
        </div>
        <div className="tw-text-center tw-p-4 tw-bg-gray-50 tw-rounded-lg">
          <div className="tw-text-xl tw-font-bold tw-text-gray-900">50K+</div>
          <div className="tw-text-sm tw-text-gray-600">Backups Saved 💾</div>
        </div>
        <div className="tw-text-center tw-p-4 tw-bg-gray-50 tw-rounded-lg">
          <div className="tw-text-xl tw-font-bold tw-text-gray-900">4.9★</div>
          <div className="tw-text-sm tw-text-gray-600">User Rating ⭐</div>
        </div>
      </div>

      {/* Honest Goals Message */}
      <div className="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4 tw-mb-8">
        <div className="tw-flex tw-items-start tw-gap-3">
          <Target className="tw-w-5 tw-h-5 tw-text-blue-600 tw-mt-0.5 tw-flex-shrink-0" />
          <div>
            <h4 className="tw-font-semibold tw-text-blue-900 tw-mb-2">
              Honest Talk! 🤝
            </h4>
            <p className="tw-text-sm tw-text-blue-800 tw-leading-relaxed">
              These numbers above are my <strong>dream goals</strong> for {config.pluginName}! 🎯 
              Right now, we're just getting started, but I believe with your help, 
              we can make these numbers real! 💪
            </p>
            <p className="tw-text-sm tw-text-blue-700 tw-mt-2 tw-italic">
              Every coffee, review, or share brings us closer to these goals! 🚀
            </p>
          </div>
        </div>
      </div>

      {/* Fun Features List */}
      <div className="tw-mb-8">
        <h3 className="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4 tw-text-center">
          What Your Coffee Money Does 💸
        </h3>
        <div className="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
          {whatYourCoffeeMoneyDoes.map((item, index) => (
            <div key={index} className="tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-shadow-sm hover:tw-shadow-md tw-transition-shadow">
              <div className="tw-flex tw-items-start tw-gap-3">
                <div className="tw-text-2xl tw-flex-shrink-0">{item.icon}</div>
                <div>
                  <h4 className="tw-font-semibold tw-text-gray-900 tw-mb-1 tw-text-sm">
                    {item.title}
                  </h4>
                  <p className="tw-text-xs tw-text-gray-600 tw-leading-relaxed">
                    {item.description}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Fun Donation Button */}
      <div className="tw-text-center tw-mb-6">
        <button
          onClick={handleDonateClick}
          className="tw-bg-blue-600 tw-text-white tw-px-6 tw-py-3 tw-rounded-lg tw-font-medium tw-flex tw-items-center tw-gap-2 tw-mx-auto hover:tw-bg-blue-700 tw-transition-colors"
        >
          <Coffee className="tw-w-5 tw-h-5" />
          <span>Buy Me a Coffee ☕</span>
        </button>
        <p className="tw-text-sm tw-text-gray-500 tw-mt-3">
          Every coffee helps keep {config.pluginName} awesome! 🙏
        </p>
      </div>

      {/* Fun Alternative Options */}
      <div className="tw-bg-gray-50 tw-rounded-lg tw-p-4 tw-mb-6">
        <h4 className="tw-text-sm tw-font-semibold tw-text-gray-900 tw-mb-3 tw-text-center">
          Other Ways to Show Love 💕
        </h4>
        <div className="tw-flex tw-justify-center tw-gap-4">
          <button className="tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-transition-colors">
            ⭐ Rate on WordPress.org
          </button>
          <button className="tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-transition-colors">
            📝 Write a Review
          </button>
          <button className="tw-text-sm tw-text-gray-600 hover:tw-text-gray-900 tw-transition-colors">
            🔗 Share with Friends
          </button>
        </div>
      </div>

      {/* Fun Footer */}
      <div className="tw-text-center tw-text-gray-500">
        <p className="tw-text-sm">
          Made with <Heart className="tw-w-4 tw-h-4 tw-inline tw-text-red-500" /> by {config.developerName} 💻
        </p>
        <p className="tw-text-xs tw-mt-1">
          {config.pluginName} will always be free - pinky promise! 🤙
        </p>
      </div>
    </div>
  );
};

export default DonationInfomation;
