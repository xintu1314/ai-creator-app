import { useState } from 'react';
import { 
  Video, 
  Image, 
  Plus, 
  ChevronDown
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import ModelSelectDialog from './ModelSelectDialog';
import ParamsDialog from './ParamsDialog';
import VideoParamsDialog from './VideoParamsDialog';
import type { Model, AspectRatio, Template } from '@/types';

interface CreationAreaProps {
  type: 'image' | 'video';
  onTypeChange: (type: 'image' | 'video') => void;
  onUseTemplate: (template: Template) => void;
}

const imageModels: Model[] = [
  { id: 'banana-pro', name: 'banana pro', description: '高性能图片生成模型', icon: 'banana', tags: ['图片生成'] },
];

const videoModels: Model[] = [
  { id: 'kling', name: '可灵', description: '高质量视频生成模型', icon: 'kling', tags: ['视频生成', '首尾帧'] },
  { id: 'sora2', name: 'sora2', description: '先进的视频生成模型', icon: 'sora', tags: ['视频生成', '首尾帧'] },
];

const tabs = [
  { id: 'video', label: '视频生成', icon: Video },
  { id: 'image', label: '图片生成', icon: Image },
];


export default function CreationArea({ type, onTypeChange }: CreationAreaProps) {
  const [selectedModel, setSelectedModel] = useState(type === 'image' ? 'banana-pro' : 'kling');
  const [imageCount, setImageCount] = useState(1);
  const [firstFrame, setFirstFrame] = useState<File | null>(null);
  const [lastFrame, setLastFrame] = useState<File | null>(null);
  const [modelDialogOpen, setModelDialogOpen] = useState(false);
  const [paramsDialogOpen, setParamsDialogOpen] = useState(false);
  const [videoParamsDialogOpen, setVideoParamsDialogOpen] = useState(false);
  const [aspectRatio, setAspectRatio] = useState<AspectRatio>(type === 'image' ? '3:4' : '16:9');
  const [videoDuration, setVideoDuration] = useState<5 | 10>(5);
  const [videoQuality, setVideoQuality] = useState<'standard' | 'high'>('standard');
  const [mode, setMode] = useState<'single' | 'multiple'>('single');
  const [quality, setQuality] = useState<'2k' | '4k'>('2k');
  const [prompt, setPrompt] = useState('');

  const models = type === 'image' ? imageModels : videoModels;
  const currentModel = models.find(m => m.id === selectedModel) || models[0];

  const handleTypeChange = (newType: string) => {
    if (newType === 'image' || newType === 'video') {
      onTypeChange(newType);
      setSelectedModel(newType === 'image' ? 'banana-pro' : 'kling');
      setAspectRatio(newType === 'image' ? '3:4' : '16:9');
    }
  };

  return (
    <div className="flex-1 p-6 overflow-auto">
      {/* Title Section */}
      <div className="text-center mb-6">
        <h1 className="text-2xl font-semibold text-[#1A1A1A] mb-3">
          {type === 'image' ? '图片创作' : '视频创作'}
        </h1>
        
      </div>

      {/* Creation Card */}
      <div className="max-w-[900px] mx-auto bg-white rounded-2xl shadow-md p-6">
        {/* Tabs */}
        <div className="flex gap-6 mb-6 border-b border-[#E5E5E5]">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = (type === 'image' && tab.id === 'image') || (type === 'video' && tab.id === 'video') || tab.id === type;
            
            return (
              <button
                key={tab.id}
                onClick={() => handleTypeChange(tab.id)}
                className={cn(
                  "flex items-center gap-2 pb-3 text-sm font-medium transition-all duration-200 relative",
                  isActive ? "text-[#3B82F6]" : "text-[#666666] hover:text-[#1A1A1A]"
                )}
              >
                <Icon className="w-4 h-4" />
                {tab.label}
                {isActive && (
                  <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full" />
                )}
              </button>
            );
          })}
        </div>

        {/* Input Area */}
        {type === 'video' ? (
          <div className="flex gap-4 mb-4">
            {/* First Frame */}
            <div className="w-[100px] h-[100px] border-2 border-dashed border-[#E5E5E5] rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all duration-200 relative">
              <input
                type="file"
                accept="image/*"
                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) setFirstFrame(file);
                }}
              />
              {firstFrame ? (
                <img 
                  src={URL.createObjectURL(firstFrame)} 
                  alt="首帧" 
                  className="w-full h-full object-cover rounded-lg"
                />
              ) : (
                <>
                  <Plus className="w-6 h-6 text-[#999999] mb-1" />
                  <span className="text-xs text-[#999999]">首帧</span>
                </>
              )}
            </div>

            {/* Last Frame */}
            <div className="w-[100px] h-[100px] border-2 border-dashed border-[#E5E5E5] rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all duration-200 relative">
              <input
                type="file"
                accept="image/*"
                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) setLastFrame(file);
                }}
              />
              {lastFrame ? (
                <img 
                  src={URL.createObjectURL(lastFrame)} 
                  alt="尾帧" 
                  className="w-full h-full object-cover rounded-lg"
                />
              ) : (
                <>
                  <Plus className="w-6 h-6 text-[#999999] mb-1" />
                  <span className="text-xs text-[#999999]">尾帧</span>
                </>
              )}
            </div>

            {/* Text Input */}
            <div className="flex-1">
              <textarea
                value={prompt}
                onChange={(e) => setPrompt(e.target.value)}
                placeholder="试试描述一段简短的故事情节，最关键的是主体、环境、时间、风格"
                className="w-full h-[100px] p-3 text-sm text-[#1A1A1A] placeholder:text-[#999999] border border-[#E5E5E5] rounded-lg resize-none focus:outline-none focus:border-[#3B82F6] transition-colors"
              />
            </div>
          </div>
        ) : (
          <div className="flex gap-4 mb-4">
            {/* Upload Area */}
            <div className="w-[100px] h-[100px] border-2 border-dashed border-[#E5E5E5] rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all duration-200">
              <Plus className="w-6 h-6 text-[#999999] mb-1" />
              <span className="text-xs text-[#999999]">添加</span>
            </div>

            {/* Text Input */}
            <div className="flex-1">
              <textarea
                value={prompt}
                onChange={(e) => setPrompt(e.target.value)}
                placeholder="输入图片生成的提示词，例如：浩瀚的银河中一艘宇宙飞船驶过"
                className="w-full h-[100px] p-3 text-sm text-[#1A1A1A] placeholder:text-[#999999] border border-[#E5E5E5] rounded-lg resize-none focus:outline-none focus:border-[#3B82F6] transition-colors"
              />
            </div>
          </div>
        )}

        {/* Parameters Bar */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            {/* Model Selector */}
            <button
              onClick={() => setModelDialogOpen(true)}
              className="flex items-center gap-2 px-3 py-2 text-sm text-[#1A1A1A] bg-[#F5F5F5] rounded-lg hover:bg-[#E5E5E5] transition-colors"
            >
              <div className={`w-4 h-4 rounded bg-gradient-to-br ${
                currentModel.icon === 'banana' 
                  ? 'from-yellow-400 to-yellow-600' 
                  : currentModel.icon === 'sora'
                    ? 'from-purple-500 to-pink-500'
                    : 'from-blue-500 to-blue-700'
              }`} />
              {currentModel.name}
              <ChevronDown className="w-4 h-4 text-[#666666]" />
            </button>

            {/* Aspect Ratio & Count / Video Ratio & Duration */}
            {type === 'image' ? (
              <button
                onClick={() => setParamsDialogOpen(true)}
                className="flex items-center gap-2 px-3 py-2 text-sm text-[#1A1A1A] bg-[#F5F5F5] rounded-lg hover:bg-[#E5E5E5] transition-colors"
              >
                <span>{aspectRatio}</span>
                <span className="text-[#999999]">·</span>
                <span>{imageCount}张</span>
                <ChevronDown className="w-4 h-4 text-[#666666]" />
              </button>
            ) : (
              <button
                onClick={() => setVideoParamsDialogOpen(true)}
                className="flex items-center gap-2 px-3 py-2 text-sm text-[#1A1A1A] bg-[#F5F5F5] rounded-lg hover:bg-[#E5E5E5] transition-colors"
              >
                <span>{aspectRatio}</span>
                <span className="text-[#999999]">·</span>
                <span>{videoDuration}s</span>
                <ChevronDown className="w-4 h-4 text-[#666666]" />
              </button>
            )}
          </div>

          {/* Generate Button */}
          <Button 
            className="h-10 px-8 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-all duration-200 hover:scale-[1.02]"
          >
            生成
          </Button>
        </div>
      </div>

      {/* Model Select Dialog */}
      <ModelSelectDialog
        open={modelDialogOpen}
        onOpenChange={setModelDialogOpen}
        models={models}
        selectedModel={selectedModel}
        onSelectModel={setSelectedModel}
        title="选择模型"
      />

      {/* Params Dialog */}
      {type === 'image' && (
        <ParamsDialog
          open={paramsDialogOpen}
          onOpenChange={setParamsDialogOpen}
          mode={mode}
          onModeChange={setMode}
          quality={quality}
          onQualityChange={setQuality}
          aspectRatio={aspectRatio}
          onAspectRatioChange={setAspectRatio}
          count={imageCount}
          onCountChange={setImageCount}
        />
      )}

      {/* Video Params Dialog */}
      {type === 'video' && (
        <VideoParamsDialog
          open={videoParamsDialogOpen}
          onOpenChange={setVideoParamsDialogOpen}
          aspectRatio={aspectRatio}
          onAspectRatioChange={setAspectRatio}
          duration={videoDuration}
          onDurationChange={setVideoDuration}
          quality={videoQuality}
          onQualityChange={setVideoQuality}
        />
      )}
    </div>
  );
}
